<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Api\V2\Concerns\BuildsApiPayloads;
use App\Http\Controllers\Controller;
use App\Http\Requests\AccountsPayable\StoreAccountPayablePaymentRequest;
use App\Http\Requests\AccountsPayable\StoreAccountPayableRequest;
use App\Http\Requests\AccountsPayable\StoreCreditorRequest;
use App\Http\Requests\AccountsPayable\UpdateAccountPayableRequest;
use App\Http\Requests\AccountsPayable\UpdateCreditorRequest;
use App\Models\AccountPayable;
use App\Models\AccountPayableInstallment;
use App\Models\AccountPayablePayment;
use App\Models\Creditor;
use App\Services\AccountsPayable\AccountPayableService;
use App\Services\AccountsPayable\CreditorService;
use App\Support\MenuAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class AccountsPayableController extends Controller
{
    use BuildsApiPayloads;

    public function __construct(
        private readonly AccountPayableService $accountService,
        private readonly CreditorService $creditorService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorizeFeature($request);
        $accounts = $this->accountService->paginateForCompany(
            (int) $request->user()->company_id,
            $request->only(['search', 'creditor_id', 'status']),
        );

        return response()->json([
            'data' => $accounts
                ->through(fn (AccountPayable $account): array => $this->accountPayload($account))
                ->items(),
            'meta' => $this->paginationMeta($accounts),
        ]);
    }

    public function store(StoreAccountPayableRequest $request): JsonResponse
    {
        $this->authorizeFeature($request);
        $account = $this->accountService->create(
            (int) $request->user()->company_id,
            $request->validated(),
            (int) $request->user()->id,
        );

        return response()->json([
            'data' => $this->accountDetailPayload($account),
        ], 201);
    }

    public function show(Request $request, int $accountPayable): JsonResponse
    {
        $this->authorizeFeature($request);
        $account = $this->accountService->findForCompany(
            (int) $request->user()->company_id,
            $accountPayable,
        );

        return response()->json([
            'data' => $this->accountDetailPayload($account),
        ]);
    }

    public function update(UpdateAccountPayableRequest $request, int $accountPayable): JsonResponse
    {
        $this->authorizeFeature($request);

        try {
            $account = $this->accountService->update(
                (int) $request->user()->company_id,
                $accountPayable,
                $request->validated(),
                (int) $request->user()->id,
            );
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'data' => $this->accountDetailPayload($account),
        ]);
    }

    public function destroy(Request $request, int $accountPayable): JsonResponse
    {
        $this->authorizeFeature($request);

        try {
            $this->accountService->delete(
                (int) $request->user()->company_id,
                $accountPayable,
                (int) $request->user()->id,
            );
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json(['message' => 'Cuenta por pagar eliminada.']);
    }

    public function storePayment(
        StoreAccountPayablePaymentRequest $request,
        int $accountPayable,
    ): JsonResponse {
        $this->authorizeFeature($request);

        try {
            $payment = $this->accountService->registerPayment(
                (int) $request->user()->company_id,
                $accountPayable,
                $request->validated(),
                (int) $request->user()->id,
            );
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'data' => $this->paymentPayload($payment->loadMissing('creditor')),
        ], 201);
    }

    public function creditors(Request $request): JsonResponse
    {
        $this->authorizeFeature($request);
        $creditors = $this->creditorService->paginateForCompany(
            (int) $request->user()->company_id,
            $request->only(['search', 'status']),
        );

        return response()->json([
            'data' => $creditors
                ->through(fn (Creditor $creditor): array => $this->creditorPayload($creditor))
                ->items(),
            'meta' => $this->paginationMeta($creditors),
        ]);
    }

    public function storeCreditor(StoreCreditorRequest $request): JsonResponse
    {
        $this->authorizeFeature($request);
        $creditor = $this->creditorService->create(
            (int) $request->user()->company_id,
            $request->validated(),
        );

        return response()->json([
            'data' => $this->creditorPayload($creditor->loadCount('accountsPayable')),
        ], 201);
    }

    public function updateCreditor(
        UpdateCreditorRequest $request,
        int $creditor,
    ): JsonResponse {
        $this->authorizeFeature($request);
        $model = $this->creditorService->findForCompany(
            (int) $request->user()->company_id,
            $creditor,
        );
        $updated = $this->creditorService->update($model, $request->validated());

        return response()->json([
            'data' => $this->creditorPayload($updated->loadCount('accountsPayable')),
        ]);
    }

    public function destroyCreditor(Request $request, int $creditor): JsonResponse
    {
        $this->authorizeFeature($request);
        $model = $this->creditorService->findForCompany(
            (int) $request->user()->company_id,
            $creditor,
        );

        try {
            $this->creditorService->delete($model);
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json(['message' => 'Acreedor eliminado.']);
    }

    private function authorizeFeature(Request $request): void
    {
        abort_unless(
            $request->user()?->can('accounts-payable.manage')
                && MenuAccess::canAccessMenu($request->user(), 'accounts-payable.index'),
            403,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function accountPayload(AccountPayable $account): array
    {
        return [
            'id' => $account->id,
            'reference' => $account->reference,
            'currency' => $account->currency,
            'creditor' => $account->creditor
                ? $this->creditorPayload($account->creditor)
                : null,
            'principal_amount' => (float) $account->principal_amount,
            'interest_rate' => (float) $account->interest_rate,
            'interest_type' => $account->interest_type,
            'payment_frequency' => $account->payment_frequency,
            'calculation_method' => $account->calculation_method,
            'term_quantity' => (int) $account->term_quantity,
            'installment_amount' => (float) $account->installment_amount,
            'total_interest' => (float) $account->total_interest,
            'total_amount' => (float) $account->total_amount,
            'paid_principal' => (float) $account->paid_principal,
            'paid_interest' => (float) $account->paid_interest,
            'paid_late_fee' => (float) $account->paid_late_fee,
            'remaining_balance' => (float) $account->remaining_balance,
            'disbursement_date' => $account->disbursement_date?->toDateString(),
            'first_payment_date' => $account->first_payment_date?->toDateString(),
            'end_date' => $account->end_date?->toDateString(),
            'status' => $account->status,
            'payments_count' => (int) ($account->payments_count ?? 0),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function accountDetailPayload(AccountPayable $account): array
    {
        return [
            ...$this->accountPayload($account),
            'late_fee_type' => $account->late_fee_type,
            'late_fee_value' => (float) $account->late_fee_value,
            'notes' => $account->notes,
            'installments' => $account->installments
                ->map(fn (AccountPayableInstallment $installment): array => $this->installmentPayload($installment))
                ->values(),
            'payments' => $account->payments
                ->map(fn (AccountPayablePayment $payment): array => $this->paymentPayload($payment))
                ->values(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function installmentPayload(AccountPayableInstallment $installment): array
    {
        $pendingPrincipal = max(0, (float) $installment->principal_amount - (float) $installment->paid_principal);
        $pendingInterest = max(0, (float) $installment->interest_amount - (float) $installment->paid_interest);
        $pendingLateFee = max(0, (float) $installment->late_fee - (float) $installment->paid_late_fee);

        return [
            'id' => $installment->id,
            'installment_number' => (int) $installment->installment_number,
            'due_date' => $installment->due_date?->toDateString(),
            'principal_amount' => (float) $installment->principal_amount,
            'interest_amount' => (float) $installment->interest_amount,
            'installment_amount' => (float) $installment->installment_amount,
            'late_fee' => (float) $installment->late_fee,
            'paid_principal' => (float) $installment->paid_principal,
            'paid_interest' => (float) $installment->paid_interest,
            'paid_late_fee' => (float) $installment->paid_late_fee,
            'total_paid' => (float) $installment->total_paid,
            'pending_principal' => $pendingPrincipal,
            'pending_interest' => $pendingInterest,
            'pending_late_fee' => $pendingLateFee,
            'pending_amount' => $pendingPrincipal + $pendingInterest + $pendingLateFee,
            'days_late' => (int) $installment->days_late,
            'status' => $installment->status,
            'paid_at' => $installment->paid_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function paymentPayload(AccountPayablePayment $payment): array
    {
        return [
            'id' => $payment->id,
            'payment_number' => $payment->payment_number,
            'account_payable_id' => $payment->account_payable_id,
            'creditor_id' => $payment->creditor_id,
            'payment_date' => $payment->payment_date?->toDateString(),
            'amount' => (float) $payment->amount,
            'principal_paid' => (float) $payment->principal_paid,
            'interest_paid' => (float) $payment->interest_paid,
            'late_fee_paid' => (float) $payment->late_fee_paid,
            'previous_balance' => (float) $payment->previous_balance,
            'new_balance' => (float) $payment->new_balance,
            'payment_method' => $payment->payment_method,
            'notes' => $payment->notes,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function creditorPayload(Creditor $creditor): array
    {
        return [
            'id' => $creditor->id,
            'name' => $creditor->name,
            'document' => $creditor->document,
            'phone' => $creditor->phone,
            'email' => $creditor->email,
            'address' => $creditor->address,
            'notes' => $creditor->notes,
            'status' => $creditor->status,
            'accounts_payable_count' => (int) ($creditor->accounts_payable_count ?? 0),
        ];
    }
}
