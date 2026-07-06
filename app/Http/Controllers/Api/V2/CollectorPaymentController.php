<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Api\V2\Concerns\BuildsApiPayloads;
use App\Http\Controllers\Api\V2\Concerns\InteractsWithCollectorPortfolio;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\Payments\PaymentReceiptShareService;
use App\Services\Payments\PaymentService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class CollectorPaymentController extends Controller
{
    use BuildsApiPayloads;
    use InteractsWithCollectorPortfolio;

    public function __construct(
        private readonly PaymentService $paymentService,
        private readonly PaymentReceiptShareService $receiptShareService,
    ) {}

    public function payments(Request $request): JsonResponse
    {
        $collector = $this->collectorForUser($request);

        $validated = $request->validate([
            'client_id' => ['nullable', 'integer'],
            'loan_id' => ['nullable', 'integer'],
            'status' => ['nullable', Rule::in(['valid', 'cancelled'])],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $payments = $this->assignedPaymentQuery($collector)
            ->with(['loan.client', 'collector', 'collectorCommission'])
            ->when($validated['client_id'] ?? null, fn (Builder $query, int $clientId): Builder => $query->where('client_id', $clientId))
            ->when($validated['loan_id'] ?? null, fn (Builder $query, int $loanId): Builder => $query->where('loan_id', $loanId))
            ->when($validated['status'] ?? null, fn (Builder $query, string $status): Builder => $query->where('status', $status))
            ->when($validated['date_from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('payment_date', '>=', $date))
            ->when($validated['date_to'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('payment_date', '<=', $date))
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->paginate((int) ($validated['per_page'] ?? 25));

        return response()->json([
            'data' => $payments->through(fn (Payment $payment): array => $this->collectorPaymentPayload($payment))->items(),
            'meta' => $this->paginationMeta($payments),
        ]);
    }

    public function payment(Request $request, int $payment): JsonResponse
    {
        $collector = $this->collectorForUser($request);

        $paymentModel = $this->assignedPaymentQuery($collector)
            ->with(['loan.client', 'collector', 'collectorCommission', 'details.installment'])
            ->whereKey($payment)
            ->firstOrFail();

        return response()->json([
            'data' => $paymentModel->status === 'valid'
                ? $this->paymentWithShareData($paymentModel)
                : $this->collectorPaymentPayload($paymentModel),
        ]);
    }

    public function storePayment(Request $request): JsonResponse
    {
        $collector = $this->collectorForUser($request);

        $replayValidated = $request->validate([
            'loan_id' => ['required', 'integer'],
            'mobile_uuid' => ['nullable', 'uuid'],
        ]);

        if (! empty($replayValidated['mobile_uuid'])) {
            $existingPayment = $this->assignedPaymentQuery($collector)
                ->with(['loan.client', 'collector', 'collectorCommission', 'details.installment'])
                ->where('loan_id', $replayValidated['loan_id'])
                ->where('mobile_uuid', $replayValidated['mobile_uuid'])
                ->first();

            if ($existingPayment) {
                return response()->json([
                    'data' => $this->collectorPaymentPayload($existingPayment),
                ]);
            }
        }

        $loanId = $request->input('loan_id');
        $validated = $request->validate([
            'loan_id' => [
                'required',
                'integer',
                Rule::exists('loans', 'id')
                    ->where('company_id', $collector->company_id)
                    ->where('collector_id', $collector->id),
            ],
            'payment_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999999.99'],
            'payment_method' => ['required', Rule::in(['cash', 'transfer', 'card', 'check', 'other'])],
            'mobile_uuid' => ['nullable', 'uuid'],
            'allocation_mode' => ['nullable', Rule::in(array_values(array_intersect(
                array_keys(enabled_payment_allocation_modes()),
                ['auto', 'principal_and_interest', 'interest_only', 'principal_only', 'current_plus_capital']
            )))],
            'excess_action' => ['nullable', Rule::in(['reject', 'prepayment', 'change'])],
            'capital_prepayment_amount' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'target_installment_id' => [
                'nullable',
                'integer',
                Rule::exists('loan_installments', 'id')
                    ->where('loan_id', $loanId)
                    ->whereNotIn('status', ['paid', 'cancelled']),
            ],
        ]);

        try {
            $payment = $this->paymentService->register([
                ...$validated,
                'allocation_mode' => $validated['allocation_mode'] ?? 'auto',
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
            'data' => $this->paymentWithShareData($payment->fresh(['loan.client', 'collector', 'collectorCommission']) ?? $payment),
        ], 201);
    }

    /**
     * Payload del cobrador + enlaces para compartir el recibo (solo si el pago es válido).
     *
     * @return array<string, mixed>
     */
    private function paymentWithShareData(Payment $payment): array
    {
        $payload = $this->collectorPaymentPayload($payment);

        if ($payment->status === 'valid') {
            $shareData = $this->receiptShareService->shareData($payment, (int) ($payment->created_by ?? 0));
            $payload['receipt_url'] = $shareData['receipt_url'];
            $payload['whatsapp_url'] = $shareData['whatsapp_url'];
        }

        return $payload;
    }
}
