<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Concerns;

use App\Models\Client;
use App\Models\Collector;
use App\Models\Loan;
use App\Models\LoanInstallment;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Base compartida por los controladores del cobrador (app de campo).
 *
 * Resuelve el cobrador desde el usuario autenticado y provee las consultas que
 * limitan todo a SU cartera (clientes/préstamos/cuotas/pagos asignados), además
 * del resumen financiero y el payload de pago con comisión. Depende de
 * BuildsApiPayloads (para `paymentPayload` base) en el controlador que lo use.
 */
trait InteractsWithCollectorPortfolio
{
    private function collectorForUser(Request $request): Collector
    {
        return Collector::query()
            ->forCompany((int) $request->user()->company_id)
            ->where('user_id', $request->user()->id)
            ->where('status', 'active')
            ->firstOrFail();
    }

    private function assignedClientQuery(Collector $collector): Builder
    {
        return Client::query()
            ->forCompany((int) $collector->company_id)
            ->where(function (Builder $query) use ($collector): void {
                $query->whereHas('loans', fn (Builder $loanQuery): Builder => $loanQuery->where('collector_id', $collector->id))
                    ->orWhereHas('routes', fn (Builder $routeQuery): Builder => $routeQuery->where('collector_id', $collector->id));
            });
    }

    private function assignedLoanQuery(Collector $collector): Builder
    {
        return Loan::query()
            ->forCompany((int) $collector->company_id)
            ->where('collector_id', $collector->id);
    }

    private function assignedInstallmentQuery(Collector $collector): Builder
    {
        return LoanInstallment::query()
            ->whereIn('status', ['pending', 'partial', 'late'])
            ->whereHas('loan', function (Builder $query) use ($collector): void {
                $query->forCompany((int) $collector->company_id)
                    ->where('collector_id', $collector->id);
            });
    }

    private function assignedInstallmentDetailQuery(Collector $collector): Builder
    {
        return LoanInstallment::query()
            ->with([
                'loan.client:id,code,full_name,identification,phone,address,status,risk_level',
                'paymentDetails.payment.loan.client',
                'paymentDetails.payment.collector',
            ])
            ->whereHas('loan', function (Builder $query) use ($collector): void {
                $query->forCompany((int) $collector->company_id)
                    ->where('collector_id', $collector->id);
            });
    }

    private function assignedPaymentQuery(Collector $collector): Builder
    {
        return Payment::query()
            ->forCompany((int) $collector->company_id)
            ->where('collector_id', $collector->id)
            ->whereHas('loan', function (Builder $query) use ($collector): void {
                $query->forCompany((int) $collector->company_id)
                    ->where('collector_id', $collector->id);
            });
    }

    /**
     * @return array<string, mixed>
     */
    private function clientFinancialSummary(Collector $collector, int $clientId): array
    {
        $loanQuery = $this->assignedLoanQuery($collector)->where('client_id', $clientId);
        $paymentQuery = $this->assignedPaymentQuery($collector)->where('client_id', $clientId)->where('status', 'valid');
        $pendingInstallmentQuery = $this->assignedInstallmentQuery($collector)
            ->whereHas('loan', fn (Builder $query): Builder => $query->where('client_id', $clientId));

        $openLoanQuery = (clone $loanQuery)->whereIn('status', ['active', 'late']);

        return [
            'active_loans' => (clone $openLoanQuery)->count(),
            'late_loans' => (clone $loanQuery)->where('status', 'late')->count(),
            'total_principal' => (float) (clone $loanQuery)->sum('principal_amount'),
            'remaining_balance' => (float) (clone $loanQuery)->sum('remaining_balance'),
            'pending_principal' => max(0.0, (float) (clone $openLoanQuery)->sum(DB::raw('principal_amount - paid_principal'))),
            'pending_interest' => max(0.0, (float) (clone $openLoanQuery)->sum(DB::raw('total_interest - paid_interest'))),
            'pending_installments' => (clone $pendingInstallmentQuery)->count(),
            'late_installments' => (clone $pendingInstallmentQuery)->where('status', 'late')->count(),
            'max_days_late' => (int) (clone $pendingInstallmentQuery)->max('days_late'),
            'total_paid' => (float) (clone $paymentQuery)->sum('amount'),
            'last_payment_date' => (clone $paymentQuery)->max('payment_date'),
        ];
    }

    /**
     * Payload de pago del cobrador: el base de BuildsApiPayloads + la comisión.
     * (Los enlaces para compartir se añaden aparte en CollectorPaymentController.)
     *
     * @return array<string, mixed>
     */
    private function collectorPaymentPayload(Payment $payment): array
    {
        $payload = $this->paymentPayload($payment);
        $payload['commission'] = $this->paymentCommissionPayload($payment);

        return $payload;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function paymentCommissionPayload(Payment $payment): ?array
    {
        if (! $payment->relationLoaded('collectorCommission')) {
            $payment->load('collectorCommission');
        }

        $commission = $payment->collectorCommission;

        if (! $commission) {
            return null;
        }

        return [
            'id' => $commission->id,
            'commission_type' => $commission->commission_type,
            'commission_value' => (float) $commission->commission_value,
            'base_amount' => (float) $commission->base_amount,
            'commission_amount' => (float) $commission->commission_amount,
            'status' => $commission->status,
            'paid_at' => $commission->paid_at?->toDateTimeString(),
        ];
    }
}
