<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Collector;
use App\Models\Loan;
use App\Models\LoanInstallment;
use App\Models\Payment;
use App\Services\Payments\PaymentService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class CollectorController extends Controller
{
    public function __construct(private readonly PaymentService $paymentService)
    {
    }

    public function summary(Request $request): JsonResponse
    {
        $collector = $this->collectorForUser($request);

        return response()->json([
            'data' => [
                'collector' => $this->collectorPayload($collector),
                'assigned_clients' => $this->assignedClientQuery($collector)->count(),
                'active_loans' => $this->assignedLoanQuery($collector)->whereIn('status', ['active', 'late'])->count(),
                'late_loans' => $this->assignedLoanQuery($collector)->where('status', 'late')->count(),
                'pending_installments' => $this->assignedInstallmentQuery($collector)->count(),
                'collected_today' => (float) Payment::query()
                    ->forCompany((int) $collector->company_id)
                    ->where('collector_id', $collector->id)
                    ->where('status', 'valid')
                    ->whereDate('payment_date', now()->toDateString())
                    ->sum('amount'),
            ],
        ]);
    }

    public function clients(Request $request): JsonResponse
    {
        $collector = $this->collectorForUser($request);

        $clients = $this->assignedClientQuery($collector)
            ->orderBy('full_name')
            ->paginate((int) $request->integer('per_page', 25));

        return response()->json([
            'data' => $clients->through(fn (Client $client): array => $this->clientPayload($client))->items(),
            'meta' => $this->paginationMeta($clients),
        ]);
    }

    public function loans(Request $request): JsonResponse
    {
        $collector = $this->collectorForUser($request);

        $loans = $this->assignedLoanQuery($collector)
            ->with('client:id,full_name,identification,phone,address')
            ->whereIn('status', ['active', 'late'])
            ->orderBy('loan_number')
            ->paginate((int) $request->integer('per_page', 25));

        return response()->json([
            'data' => $loans->through(fn (Loan $loan): array => $this->loanPayload($loan))->items(),
            'meta' => $this->paginationMeta($loans),
        ]);
    }

    public function installments(Request $request): JsonResponse
    {
        $collector = $this->collectorForUser($request);

        $installments = $this->assignedInstallmentQuery($collector)
            ->with('loan.client:id,full_name,identification,phone,address')
            ->orderBy('due_date')
            ->orderBy('installment_number')
            ->paginate((int) $request->integer('per_page', 25));

        return response()->json([
            'data' => $installments->through(fn (LoanInstallment $installment): array => $this->installmentPayload($installment))->items(),
            'meta' => $this->paginationMeta($installments),
        ]);
    }

    public function storePayment(Request $request): JsonResponse
    {
        $collector = $this->collectorForUser($request);

        $validated = $request->validate([
            'loan_id' => [
                'required',
                'integer',
                Rule::exists('loans', 'id')
                    ->where('company_id', $collector->company_id)
                    ->where('collector_id', $collector->id)
                    ->whereIn('status', ['active', 'late']),
            ],
            'payment_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999999.99'],
            'payment_method' => ['required', Rule::in(['cash', 'transfer', 'card', 'check', 'other'])],
        ]);

        try {
            $payment = $this->paymentService->register([
                ...$validated,
                'collector_id' => $collector->id,
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
            'data' => $this->paymentPayload($payment->fresh(['loan.client', 'collector']) ?? $payment),
        ], 201);
    }

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
            ->whereHas('loans', fn (Builder $query): Builder => $query->where('collector_id', $collector->id));
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
                    ->where('collector_id', $collector->id)
                    ->whereIn('status', ['active', 'late']);
            });
    }

    /**
     * @return array<string, mixed>
     */
    private function collectorPayload(Collector $collector): array
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
    private function clientPayload(Client $client): array
    {
        return [
            'id' => $client->id,
            'code' => $client->code,
            'full_name' => $client->full_name,
            'identification' => $client->identification,
            'phone' => $client->phone,
            'address' => $client->address,
            'status' => $client->status,
            'risk_level' => $client->risk_level,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function loanPayload(Loan $loan): array
    {
        return [
            'id' => $loan->id,
            'loan_number' => $loan->loan_number,
            'client' => $loan->client ? $this->clientPayload($loan->client) : null,
            'principal_amount' => (float) $loan->principal_amount,
            'installment_amount' => (float) $loan->installment_amount,
            'total_amount' => (float) $loan->total_amount,
            'remaining_balance' => (float) $loan->remaining_balance,
            'payment_frequency' => $loan->payment_frequency,
            'status' => $loan->status,
            'start_date' => $loan->start_date?->toDateString(),
            'first_payment_date' => $loan->first_payment_date?->toDateString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function installmentPayload(LoanInstallment $installment): array
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
            'days_late' => (int) $installment->days_late,
            'status' => $installment->status,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function paymentPayload(Payment $payment): array
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
            'status' => $payment->status,
        ];
    }

    /**
     * @return array<string, int|null>
     */
    private function paginationMeta($paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];
    }
}
