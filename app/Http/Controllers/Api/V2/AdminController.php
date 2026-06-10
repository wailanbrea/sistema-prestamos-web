<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Api\V2\Concerns\BuildsApiPayloads;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Loan;
use App\Models\LoanInstallment;
use App\Models\Payment;
use App\Services\Loans\LoanService;
use App\Services\Payments\PaymentReceiptShareService;
use App\Services\Payments\PaymentService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

/**
 * Endpoints de back-office para la app móvil (roles Administrador/Supervisor):
 * vista global de clientes y préstamos de la empresa, y bandeja de aprobación
 * de préstamos pendientes. A diferencia de CollectorController, no se restringe
 * a la cartera de un cobrador; solo se aísla por empresa.
 */
class AdminController extends Controller
{
    use BuildsApiPayloads;

    public function __construct(
        private readonly LoanService $loanService,
        private readonly PaymentService $paymentService,
        private readonly PaymentReceiptShareService $receiptShareService,
    ) {
    }

    public function clients(Request $request): JsonResponse
    {
        $companyId = (int) $request->user()->company_id;
        $search = $request->string('search')->toString();

        $clients = Client::query()
            ->forCompany($companyId)
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $inner) use ($search): void {
                    $inner->where('full_name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('identification', 'like', "%{$search}%");
                });
            })
            ->orderBy('full_name')
            ->paginate((int) $request->integer('per_page', 25));

        return response()->json([
            'data' => $clients->through(fn (Client $client): array => $this->clientPayload($client))->items(),
            'meta' => $this->paginationMeta($clients),
        ]);
    }

    public function client(Request $request, int $client): JsonResponse
    {
        $companyId = (int) $request->user()->company_id;

        $clientModel = Client::query()
            ->forCompany($companyId)
            ->with([
                'references:id,client_id,name,phone,relationship,address',
                'routes:id,name',
            ])
            ->whereKey($client)
            ->firstOrFail();

        $loans = Loan::query()
            ->forCompany($companyId)
            ->with('client:id,code,full_name,identification,phone,address,status,risk_level')
            ->where('client_id', $clientModel->id)
            ->orderByRaw("case when status in ('active', 'late') then 0 else 1 end")
            ->orderByDesc('id')
            ->get();

        $installments = LoanInstallment::query()
            ->whereIn('status', ['pending', 'partial', 'late'])
            ->with('loan.client:id,code,full_name,identification,phone,address,status,risk_level')
            ->whereHas('loan', fn (Builder $query): Builder => $query->forCompany($companyId)->where('client_id', $clientModel->id))
            ->orderBy('due_date')
            ->limit(10)
            ->get();

        $payments = Payment::query()
            ->forCompany($companyId)
            ->with(['loan.client', 'collector'])
            ->where('client_id', $clientModel->id)
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        return response()->json([
            'data' => [
                ...$this->clientDetailPayload($clientModel),
                'summary' => $this->clientFinancialSummary($companyId, (int) $clientModel->id),
                'loans' => $loans->map(fn (Loan $loan): array => $this->loanPayload($loan))->values(),
                'pending_installments' => $installments->map(fn (LoanInstallment $installment): array => $this->installmentPayload($installment))->values(),
                'recent_payments' => $payments->map(fn (Payment $payment): array => $this->paymentPayload($payment))->values(),
            ],
        ]);
    }

    public function loans(Request $request): JsonResponse
    {
        $companyId = (int) $request->user()->company_id;

        $validated = $request->validate([
            'status' => ['nullable', Rule::in(['active', 'late', 'paid', 'refinanced', 'cancelled', 'legal', 'written_off', 'pending'])],
            'collector_id' => ['nullable', 'integer'],
            'search' => ['nullable', 'string', 'max:120'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $loans = Loan::query()
            ->forCompany($companyId)
            ->with('client:id,code,full_name,identification,phone,address,status,risk_level')
            ->when($validated['status'] ?? null, fn (Builder $query, string $status): Builder => $query->where('status', $status))
            ->when($validated['collector_id'] ?? null, fn (Builder $query, int $collectorId): Builder => $query->where('collector_id', $collectorId))
            ->when($validated['search'] ?? null, fn (Builder $query, string $search): Builder => $query
                ->where(fn (Builder $inner) => $inner
                    ->where('loan_number', 'like', "%{$search}%")
                    ->orWhereHas('client', fn (Builder $c) => $c->where('full_name', 'like', "%{$search}%"))))
            ->orderByDesc('id')
            ->paginate((int) ($validated['per_page'] ?? 25));

        return response()->json([
            'data' => $loans->through(fn (Loan $loan): array => $this->loanPayload($loan))->items(),
            'meta' => $this->paginationMeta($loans),
        ]);
    }

    public function loan(Request $request, int $loan): JsonResponse
    {
        $companyId = (int) $request->user()->company_id;

        $loanModel = Loan::query()
            ->forCompany($companyId)
            ->with([
                'client:id,code,full_name,identification,phone,address,status,risk_level',
                'installments' => fn ($query) => $query->orderBy('installment_number'),
                'payments' => fn ($query) => $query->with(['loan.client', 'collector'])->orderByDesc('payment_date')->orderByDesc('id'),
            ])
            ->whereKey($loan)
            ->firstOrFail();

        return response()->json([
            'data' => [
                ...$this->loanDetailPayload($loanModel),
                'installments' => $loanModel->installments
                    ->map(fn (LoanInstallment $installment): array => $this->installmentPayload($installment))
                    ->values(),
                'payments' => $loanModel->payments
                    ->map(fn (Payment $payment): array => $this->paymentPayload($payment))
                    ->values(),
            ],
        ]);
    }

    public function approvals(Request $request): JsonResponse
    {
        $companyId = (int) $request->user()->company_id;

        $loans = Loan::query()
            ->forCompany($companyId)
            ->with('client:id,code,full_name,identification,phone,address,status,risk_level')
            ->where('status', 'pending')
            ->orderBy('created_at')
            ->get();

        return response()->json([
            'data' => $loans->map(fn (Loan $loan): array => $this->loanPayload($loan))->values(),
        ]);
    }

    public function approveLoan(Request $request, int $loan): JsonResponse
    {
        $companyId = (int) $request->user()->company_id;
        $loanModel = Loan::query()->forCompany($companyId)->whereKey($loan)->firstOrFail();

        try {
            $approved = $this->loanService->approve($companyId, (int) $request->user()->id, $loanModel);
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'data' => $this->loanPayload($approved->loadMissing('client')),
        ]);
    }

    public function rejectLoan(Request $request, int $loan): JsonResponse
    {
        $companyId = (int) $request->user()->company_id;
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $loanModel = Loan::query()->forCompany($companyId)->whereKey($loan)->firstOrFail();

        try {
            $rejected = $this->loanService->reject($companyId, (int) $request->user()->id, $loanModel, $validated['reason'] ?? null);
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'data' => $this->loanPayload($rejected->loadMissing('client')),
        ]);
    }

    /**
     * Registra un pago desde el back-office (Administrador). A diferencia del
     * endpoint del cobrador, acepta cualquier préstamo activo/atrasado de la
     * empresa; el cobro queda atribuido al cobrador asignado al préstamo.
     * Idempotente por mobile_uuid (mismo contrato que collector/payments).
     */
    public function storePayment(Request $request): JsonResponse
    {
        $companyId = (int) $request->user()->company_id;

        $replayValidated = $request->validate([
            'loan_id' => ['required', 'integer'],
            'mobile_uuid' => ['nullable', 'uuid'],
        ]);

        if (! empty($replayValidated['mobile_uuid'])) {
            $existingPayment = Payment::query()
                ->forCompany($companyId)
                ->with(['loan.client', 'collector', 'details.installment'])
                ->where('loan_id', $replayValidated['loan_id'])
                ->where('mobile_uuid', $replayValidated['mobile_uuid'])
                ->first();

            if ($existingPayment) {
                return response()->json([
                    'data' => $this->paymentWithShareData($existingPayment),
                ]);
            }
        }

        $validated = $request->validate([
            'loan_id' => [
                'required',
                'integer',
                Rule::exists('loans', 'id')
                    ->where('company_id', $companyId)
                    ->whereIn('status', ['active', 'late']),
            ],
            'payment_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999999.99'],
            'payment_method' => ['required', Rule::in(['cash', 'transfer', 'card', 'check', 'other'])],
            'mobile_uuid' => ['nullable', 'uuid'],
        ]);

        try {
            // collector_id se omite a propósito: PaymentService lo toma del préstamo.
            $payment = $this->paymentService->register([
                ...$validated,
                'created_by' => $request->user()->id,
            ]);
        } catch (InvalidArgumentException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'errors' => [
                    'amount' => [$exception->getMessage()],
                ],
            ], 422);
        }

        return response()->json([
            'data' => $this->paymentWithShareData($payment->fresh(['loan.client', 'collector']) ?? $payment),
        ], 201);
    }

    /**
     * @return array<string, mixed>
     */
    private function paymentWithShareData(Payment $payment): array
    {
        $payload = $this->paymentPayload($payment);

        if ($payment->status === 'valid') {
            $shareData = $this->receiptShareService->shareData($payment, (int) ($payment->created_by ?? 0));
            $payload['receipt_url'] = $shareData['receipt_url'];
            $payload['whatsapp_url'] = $shareData['whatsapp_url'];
        }

        return $payload;
    }

    /**
     * Resumen financiero del cliente a nivel empresa (sin restricción de cobrador).
     *
     * @return array<string, mixed>
     */
    private function clientFinancialSummary(int $companyId, int $clientId): array
    {
        $loanQuery = Loan::query()->forCompany($companyId)->where('client_id', $clientId);
        $paymentQuery = Payment::query()->forCompany($companyId)->where('client_id', $clientId)->where('status', 'valid');
        $pendingInstallmentQuery = LoanInstallment::query()
            ->whereIn('status', ['pending', 'partial', 'late'])
            ->whereHas('loan', fn (Builder $query): Builder => $query->forCompany($companyId)->where('client_id', $clientId));

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
}
