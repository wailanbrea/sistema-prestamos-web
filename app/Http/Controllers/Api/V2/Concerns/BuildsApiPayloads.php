<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Concerns;

use App\Models\Client;
use App\Models\Collector;
use App\Models\Loan;
use App\Models\LoanInstallment;
use App\Models\Payment;

/**
 * Constructores de payload JSON compartidos por los controladores de la API v2
 * (cobrador y administrador). Centraliza la forma de los recursos de dominio
 * para que la app móvil reciba estructuras idénticas en ambos modos (cartera
 * propia del cobrador o vista global del administrador).
 */
trait BuildsApiPayloads
{
    /**
     * @return array<string, mixed>
     */
    protected function collectorPayload(Collector $collector): array
    {
        return [
            'id' => $collector->id,
            'name' => $collector->name,
            'phone' => $collector->phone,
            'commission_type' => $collector->commission_type,
            'commission_value' => (float) $collector->commission_value,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function clientPayload(Client $client): array
    {
        return [
            'id' => $client->id,
            'code' => $client->code,
            'full_name' => $client->full_name,
            'identification' => $client->identification,
            'phone' => $client->phone,
            'address' => $client->address,
            'latitude' => $client->latitude === null ? null : (float) $client->latitude,
            'longitude' => $client->longitude === null ? null : (float) $client->longitude,
            'location_reference' => $client->location_reference,
            'status' => $client->status,
            'risk_level' => $client->risk_level,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function clientDetailPayload(Client $client): array
    {
        return [
            ...$this->clientPayload($client),
            'secondary_phone' => $client->secondary_phone,
            'email' => $client->email,
            'workplace' => $client->workplace,
            'workplace_phone' => $client->workplace_phone,
            'monthly_income' => (float) $client->monthly_income,
            'notes' => $client->notes,
            'references' => $client->references
                ->map(fn ($reference): array => [
                    'id' => $reference->id,
                    'name' => $reference->name,
                    'phone' => $reference->phone,
                    'relationship' => $reference->relationship,
                    'address' => $reference->address,
                ])
                ->values(),
            'routes' => $client->routes
                ->map(fn ($route): array => [
                    'id' => $route->id,
                    'name' => $route->name,
                ])
                ->values(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function loanPayload(Loan $loan): array
    {
        return [
            'id' => $loan->id,
            'loan_number' => $loan->loan_number,
            'client' => $loan->client ? $this->clientPayload($loan->client) : null,
            'principal_amount' => (float) $loan->principal_amount,
            'interest_rate' => (float) $loan->interest_rate,
            'interest_type' => $loan->interest_type,
            'calculation_method' => $loan->calculation_method,
            'term_quantity' => (int) $loan->term_quantity,
            'installment_amount' => (float) $loan->installment_amount,
            'total_interest' => (float) $loan->total_interest,
            'total_amount' => (float) $loan->total_amount,
            'paid_principal' => (float) $loan->paid_principal,
            'paid_interest' => (float) $loan->paid_interest,
            'paid_late_fee' => (float) $loan->paid_late_fee,
            'remaining_balance' => (float) $loan->remaining_balance,
            'payment_frequency' => $loan->payment_frequency,
            'status' => $loan->status,
            'start_date' => $loan->start_date?->toDateString(),
            'first_payment_date' => $loan->first_payment_date?->toDateString(),
            'end_date' => $loan->end_date?->toDateString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function loanDetailPayload(Loan $loan): array
    {
        // Cuotas vencidas: vencimiento ya pasado y no saldadas. El monto es lo que
        // aún se debe de cada cuota (cuota programada menos lo ya pagado).
        $today = now()->startOfDay();
        $overdueInstallments = $loan->installments->filter(
            fn (LoanInstallment $installment) => ! in_array($installment->status, ['paid', 'cancelled'], true)
                && $installment->due_date !== null
                && $installment->due_date->lt($today),
        );
        $overdueTotal = (float) $overdueInstallments->sum(
            fn (LoanInstallment $installment) => max(0, (float) $installment->installment_amount - (float) $installment->paid_principal - (float) $installment->paid_interest),
        );
        // Mora pendiente acumulada en las cuotas vencidas.
        $overdueLateFee = (float) $overdueInstallments->sum(
            fn (LoanInstallment $installment) => max(0, (float) $installment->late_fee - (float) $installment->paid_late_fee),
        );

        return [
            ...$this->loanPayload($loan),
            'collector_id' => $loan->collector_id,
            'collector_name' => $loan->collector?->name,
            'currency' => $loan->currency,
            'allows_capital_prepayment' => (bool) $loan->allows_capital_prepayment,
            'late_fee_type' => $loan->late_fee_type,
            'late_fee_value' => (float) $loan->late_fee_value,
            'guarantee_description' => $loan->guarantee_description,
            'notes' => $loan->notes,
            'summary' => [
                'installments_total' => $loan->installments->count(),
                'installments_pending' => $loan->installments->whereIn('status', ['pending', 'partial', 'late'])->count(),
                'installments_late' => $loan->installments->where('status', 'late')->count(),
                'payments_total' => $loan->payments->where('status', 'valid')->count(),
                'amount_paid' => (float) $loan->payments->where('status', 'valid')->sum('amount'),
                'overdue_installments_count' => $overdueInstallments->count(),
                'overdue_installments_total' => $overdueTotal,
                'overdue_late_fee_total' => $overdueLateFee,
                'total_due_today' => $overdueTotal + $overdueLateFee,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function installmentPayload(LoanInstallment $installment): array
    {
        return [
            'id' => $installment->id,
            'loan_id' => $installment->loan_id,
            'loan_number' => $installment->loan?->loan_number,
            'client' => $installment->loan?->client ? $this->clientPayload($installment->loan->client) : null,
            'installment_number' => $installment->installment_number,
            'due_date' => $installment->due_date?->toDateString(),
            'principal_amount' => (float) $installment->principal_amount,
            'interest_amount' => (float) $installment->interest_amount,
            'late_fee' => (float) $installment->late_fee,
            'installment_amount' => (float) $installment->installment_amount,
            'total_paid' => (float) $installment->total_paid,
            'paid_principal' => (float) $installment->paid_principal,
            'paid_interest' => (float) $installment->paid_interest,
            'paid_late_fee' => (float) $installment->paid_late_fee,
            'pending_amount' => max(0.0, (float) $installment->installment_amount + (float) $installment->late_fee - (float) $installment->total_paid),
            'days_late' => in_array($installment->status, ['paid', 'cancelled'], true) ? 0 : (int) $installment->days_late,
            'status' => $installment->status,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function installmentDetailPayload(LoanInstallment $installment): array
    {
        return [
            ...$this->installmentPayload($installment),
            'payments' => $installment->paymentDetails
                ->map(fn ($detail): array => [
                    'id' => $detail->id,
                    'payment_id' => $detail->payment_id,
                    'receipt_number' => $detail->payment?->receipt_number,
                    'payment_date' => $detail->payment?->payment_date?->toDateString(),
                    'payment_method' => $detail->payment?->payment_method,
                    'payment_status' => $detail->payment?->status,
                    'principal_paid' => (float) $detail->principal_paid,
                    'interest_paid' => (float) $detail->interest_paid,
                    'late_fee_paid' => (float) $detail->late_fee_paid,
                    'amount_paid' => (float) $detail->amount_paid,
                ])
                ->values(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function paymentPayload(Payment $payment): array
    {
        return [
            'id' => $payment->id,
            'receipt_number' => $payment->receipt_number,
            'loan_id' => $payment->loan_id,
            'loan_number' => $payment->loan?->loan_number,
            'client' => $payment->client ? $this->clientPayload($payment->client) : null,
            'collector' => $payment->collector ? $this->collectorPayload($payment->collector) : null,
            'payment_date' => $payment->payment_date?->toDateString(),
            'amount' => (float) $payment->amount,
            'principal_paid' => (float) $payment->principal_paid,
            'interest_paid' => (float) $payment->interest_paid,
            'late_fee_paid' => (float) $payment->late_fee_paid,
            'previous_balance' => (float) $payment->previous_balance,
            'new_balance' => (float) $payment->new_balance,
            'payment_method' => $payment->payment_method,
            'mobile_uuid' => $payment->mobile_uuid,
            'status' => $payment->status,
            'details' => $payment->relationLoaded('details')
                ? $payment->details->map(fn ($detail): array => [
                    'id' => $detail->id,
                    'installment_id' => $detail->installment_id,
                    'installment_number' => $detail->installment?->installment_number,
                    'principal_paid' => (float) $detail->principal_paid,
                    'interest_paid' => (float) $detail->interest_paid,
                    'late_fee_paid' => (float) $detail->late_fee_paid,
                    'amount_paid' => (float) $detail->amount_paid,
                ])->values()
                : [],
        ];
    }

    /**
     * @param  \Illuminate\Contracts\Pagination\LengthAwarePaginator<int, mixed>  $paginator
     * @return array<string, int|null>
     */
    protected function paginationMeta($paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];
    }
}
