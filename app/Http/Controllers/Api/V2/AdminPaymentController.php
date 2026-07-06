<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Api\V2\Concerns\BuildsApiPayloads;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cash\StoreManualCashMovementRequest;
use App\Http\Requests\Payments\CancelPaymentRequest;
use App\Models\Payment;
use App\Services\Cash\ManualCashMovementService;
use App\Services\Payments\PaymentReceiptShareService;
use App\Services\Payments\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class AdminPaymentController extends Controller
{
    use BuildsApiPayloads;

    public function __construct(
        private readonly PaymentService $paymentService,
        private readonly PaymentReceiptShareService $receiptShareService,
        private readonly ManualCashMovementService $cashMovementService,
    ) {}

    public function cancelPayment(CancelPaymentRequest $request, int $payment): JsonResponse
    {
        $companyId = (int) $request->user()->company_id;
        $validated = $request->validated();

        try {
            $cancelled = $this->paymentService->cancel(
                companyId: $companyId,
                paymentId: $payment,
                cancelledBy: (int) $request->user()->id,
                reason: $validated['cancellation_reason'],
            );
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json(['data' => $this->paymentPayload($cancelled->loadMissing(['loan.client', 'collector']))]);
    }

    public function storeMovement(StoreManualCashMovementRequest $request): JsonResponse
    {
        $companyId = (int) $request->user()->company_id;

        $this->cashMovementService->create($companyId, $request->validated(), (int) $request->user()->id);

        return response()->json(['message' => 'Movimiento registrado.'], 201);
    }

    /**
     * Historial de pagos de la empresa (back-office). Devuelve los últimos
     * cobros de toda la cartera para mostrar el "último recibo" en el dashboard.
     */
    public function payments(Request $request): JsonResponse
    {
        $companyId = (int) $request->user()->company_id;

        $validated = $request->validate([
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $payments = Payment::query()
            ->forCompany($companyId)
            ->with(['loan.client', 'collector', 'collectorCommission'])
            ->where('status', 'valid')
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->paginate((int) ($validated['per_page'] ?? 25));

        return response()->json([
            'data' => $payments->through(fn (Payment $p): array => $this->paymentPayload($p))->items(),
            'meta' => $this->paginationMeta($payments),
        ]);
    }

    /**
     * Detalle de un pago de la empresa (back-office), con datos para compartir.
     * A diferencia del endpoint del cobrador, no se limita a la cartera de un
     * cobrador, por lo que el Administrador puede ver cualquier recibo.
     */
    public function payment(Request $request, int $payment): JsonResponse
    {
        $companyId = (int) $request->user()->company_id;

        $paymentModel = Payment::query()
            ->forCompany($companyId)
            ->with(['loan.client', 'collector', 'collectorCommission', 'details.installment'])
            ->whereKey($payment)
            ->firstOrFail();

        return response()->json([
            'data' => $paymentModel->status === 'valid'
                ? $this->paymentWithShareData($paymentModel)
                : $this->paymentPayload($paymentModel),
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

        $loanId = $request->input('loan_id');
        $validated = $request->validate([
            'loan_id' => [
                'required',
                'integer',
                Rule::exists('loans', 'id')
                    ->where('company_id', $companyId),
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
            // collector_id se omite a propósito: PaymentService lo toma del préstamo.
            $payment = $this->paymentService->register([
                ...$validated,
                'allocation_mode' => $validated['allocation_mode'] ?? 'auto',
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
}
