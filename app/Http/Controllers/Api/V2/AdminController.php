<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Api\V2\Concerns\BuildsApiPayloads;
use App\Http\Controllers\Controller;
use App\Http\Requests\Cash\StoreManualCashMovementRequest;
use App\Http\Requests\Clients\StoreClientRequest;
use App\Http\Requests\Clients\UpdateClientRequest;
use App\Http\Requests\Collectors\StoreCollectorRequest;
use App\Http\Requests\Collectors\UpdateCollectorRequest;
use App\Http\Requests\Loans\StoreLoanRequest;
use App\Http\Requests\Loans\UpdateLoanRequest;
use App\Http\Requests\LoanQuotes\StoreLoanQuoteRequest;
use App\Http\Requests\Payments\CancelPaymentRequest;
use App\Models\Client;
use App\Models\Collector;
use App\Models\CollectorCommission;
use App\Models\Document;
use App\Models\Loan;
use App\Models\LoanInstallment;
use App\Models\LoanQuote;
use App\Models\Payment;
use App\Services\Cash\ManualCashMovementService;
use App\Services\Clients\ClientRegistrationLinkService;
use App\Services\Clients\ClientService;
use App\Services\Collectors\CollectorCommissionService;
use App\Services\Collectors\CollectorService;
use App\Services\Documents\DocumentGenerationService;
use App\Services\Documents\DocumentShareService;
use App\Services\Loans\LoanQuoteService;
use App\Services\Loans\LoanService;
use App\Services\Notifications\EventNotifier;
use App\Services\Payments\PaymentReceiptShareService;
use App\Services\Payments\PaymentService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class AdminController extends Controller
{
    use BuildsApiPayloads;

    public function __construct(
        private readonly LoanService $loanService,
        private readonly PaymentService $paymentService,
        private readonly PaymentReceiptShareService $receiptShareService,
        private readonly DocumentGenerationService $documentGenerationService,
        private readonly DocumentShareService $documentShareService,
        private readonly ClientService $clientService,
        private readonly ClientRegistrationLinkService $registrationLinkService,
        private readonly CollectorService $collectorService,
        private readonly CollectorCommissionService $collectorCommissionService,
        private readonly ManualCashMovementService $cashMovementService,
        private readonly LoanQuoteService $loanQuoteService,
        private readonly EventNotifier $notifier,
    ) {
    }

    /**
     * Alta de cliente desde la app móvil: mismo FormRequest que la web
     * (clients.create se valida en authorize()).
     */
    public function storeClient(StoreClientRequest $request): JsonResponse
    {
        $client = $this->clientService->create((int) $request->user()->company_id, $request->validated());

        return response()->json([
            'data' => $this->clientPayload($client),
        ], 201);
    }

    public function updateClient(UpdateClientRequest $request, int $client): JsonResponse
    {
        $companyId = (int) $request->user()->company_id;
        $clientModel = $this->clientService->findForCompany($companyId, $client);
        $updated = $this->clientService->update($clientModel, $request->validated());

        return response()->json(['data' => $this->clientDetailPayload($updated->load(['references', 'routes']))]);
    }

    public function deleteClient(Request $request, int $client): JsonResponse
    {
        abort_unless($request->user()?->can('clients.delete'), 403);
        $companyId = (int) $request->user()->company_id;
        $clientModel = $this->clientService->findForCompany($companyId, $client);

        try {
            $this->clientService->delete($clientModel);
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json(['message' => 'Cliente eliminado.']);
    }

    public function deleteLoan(Request $request, int $loan): JsonResponse
    {
        abort_unless($request->user()?->can('loans.delete'), 403);
        $companyId = (int) $request->user()->company_id;
        $loanModel = Loan::query()->forCompany($companyId)->whereKey($loan)->firstOrFail();

        try {
            $this->loanService->delete($companyId, (int) $request->user()->id, $loanModel);
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json(['message' => 'Préstamo eliminado.']);
    }

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

    public function storeCollector(StoreCollectorRequest $request): JsonResponse
    {
        $companyId = (int) $request->user()->company_id;
        $collector = $this->collectorService->create($companyId, $request->validated());

        return response()->json(['data' => $this->collectorDetailPayload($collector->load('commissions'))], 201);
    }

    public function showCollector(Request $request, int $collector): JsonResponse
    {
        $companyId = (int) $request->user()->company_id;

        $model = Collector::query()
            ->where('company_id', $companyId)
            ->with([
                'commissions' => fn ($q) => $q->orderByDesc('id')->limit(50),
            ])
            ->whereKey($collector)
            ->firstOrFail();

        $stats = [
            'active_loans' => Loan::query()->forCompany($companyId)->where('collector_id', $model->id)->whereIn('status', ['active', 'late'])->count(),
            'late_loans' => Loan::query()->forCompany($companyId)->where('collector_id', $model->id)->where('status', 'late')->count(),
        ];

        return response()->json(['data' => [...$this->collectorDetailPayload($model), 'stats' => $stats]]);
    }

    public function updateCollector(UpdateCollectorRequest $request, int $collector): JsonResponse
    {
        $companyId = (int) $request->user()->company_id;
        $model = Collector::query()->where('company_id', $companyId)->whereKey($collector)->firstOrFail();
        $updated = $this->collectorService->update($model, $request->validated());

        return response()->json(['data' => $this->collectorDetailPayload($updated->load('commissions'))]);
    }

    public function payCollectorCommission(Request $request, int $collector, int $commission): JsonResponse
    {
        $companyId = (int) $request->user()->company_id;

        try {
            $paid = $this->collectorCommissionService->pay(
                companyId: $companyId,
                collectorId: $collector,
                commissionId: $commission,
                paidBy: (int) $request->user()->id,
            );
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json(['data' => $this->commissionPayload($paid)]);
    }

    public function storeMovement(StoreManualCashMovementRequest $request): JsonResponse
    {
        $companyId = (int) $request->user()->company_id;

        $this->cashMovementService->create($companyId, $request->validated(), (int) $request->user()->id);

        return response()->json(['message' => 'Movimiento registrado.'], 201);
    }

    /** Cobradores activos de la empresa (para el selector del formulario de préstamo). */
    public function collectors(Request $request): JsonResponse
    {
        $companyId = (int) $request->user()->company_id;

        $collectors = Collector::query()
            ->where('company_id', $companyId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json([
            'data' => $collectors->map(fn (Collector $c): array => [
                'id' => $c->id,
                'name' => $c->name,
            ])->values(),
        ]);
    }

    /**
     * Alta de préstamo desde la app móvil: mismo FormRequest que la web.
     * Acepta `quote_id` opcional para pre-rellenar desde una cotización.
     */
    public function storeLoan(StoreLoanRequest $request): JsonResponse
    {
        if ($request->filled('quote_id')) {
            abort_unless($request->user()?->can('quotes.convert'), 403);
        }

        $loan = $this->loanService->create(
            companyId: (int) $request->user()->company_id,
            userId: $request->user()?->id,
            data: $request->validated(),
        );

        $this->notifier->loanCreated($loan, $request->user()?->id);

        return response()->json([
            'data' => $this->loanPayload($loan->loadMissing('client', 'collector')),
        ], 201);
    }

    /** Actualiza los datos de un préstamo. El FormRequest distingue campos siempre editables vs. solo-si-no-hay-pagos. */
    public function updateLoan(UpdateLoanRequest $request, int $loan): JsonResponse
    {
        $companyId = (int) $request->user()->company_id;
        $loanModel = Loan::query()->forCompany($companyId)->whereKey($loan)->firstOrFail();

        try {
            $updated = $this->loanService->update(
                companyId: $companyId,
                userId: (int) $request->user()->id,
                loan: $loanModel,
                data: $request->validated(),
            );
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'data' => $this->loanDetailPayload(
                $updated->loadMissing([
                    'client:id,code,full_name,identification,phone,address,status,risk_level',
                    'collector:id,name',
                    'installments',
                    'payments.loan.client',
                    'payments.collector',
                ])
            ),
        ]);
    }

    /** Genera un link de auto-registro de cliente y devuelve la URL del formulario y el link de WhatsApp. */
    public function createRegistrationLink(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'recipient_name' => ['nullable', 'string', 'max:255'],
            'recipient_phone' => ['nullable', 'string', 'max:30'],
        ]);

        abort_unless($request->user()?->can('clients.create'), 403);

        $link = $this->registrationLinkService->create(
            companyId: (int) $request->user()->company_id,
            data: $validated,
            createdBy: (int) $request->user()->id,
        );

        $formUrl = route('client-registration.show', ['token' => $link->token]);

        $whatsappUrl = null;
        $phone = $link->recipient_phone ? preg_replace('/\D+/', '', (string) $link->recipient_phone) : null;
        if ($phone) {
            $message = ($link->recipient_name ? "Hola {$link->recipient_name}, " : 'Hola, ')
                . 'completa tu formulario de registro aquí: '
                . $formUrl;
            $whatsappUrl = 'https://wa.me/' . $phone . '?text=' . rawurlencode($message);
        }

        return response()->json([
            'data' => [
                'form_url' => $formUrl,
                'whatsapp_url' => $whatsappUrl,
            ],
        ], 201);
    }

    public function quotes(Request $request): JsonResponse
    {
        $companyId = (int) $request->user()->company_id;

        $quotes = LoanQuote::query()
            ->forCompany($companyId)
            ->with('client:id,full_name,identification')
            ->orderByDesc('id')
            ->paginate((int) $request->integer('per_page', 25));

        return response()->json([
            'data' => $quotes->through(fn (LoanQuote $quote): array => $this->quotePayload($quote))->items(),
            'meta' => $this->paginationMeta($quotes),
        ]);
    }

    public function storeQuote(StoreLoanQuoteRequest $request): JsonResponse
    {
        $quote = $this->loanQuoteService->create(
            companyId: (int) $request->user()->company_id,
            userId: $request->user()?->id,
            data: $request->validated(),
        );

        return response()->json([
            'data' => $this->quotePayload($quote->loadMissing('client'), withSchedule: true),
        ], 201);
    }

    public function quote(Request $request, int $quote): JsonResponse
    {
        $model = $this->loanQuoteService->findForCompany((int) $request->user()->company_id, $quote);

        return response()->json([
            'data' => $this->quotePayload($model, withSchedule: true),
        ]);
    }

    public function destroyQuote(Request $request, int $quote): JsonResponse
    {
        $model = $this->loanQuoteService->findForCompany((int) $request->user()->company_id, $quote);

        try {
            $this->loanQuoteService->delete($model);
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json(['message' => 'Cotización eliminada.']);
    }

    /**
     * @return array<string, mixed>
     */
    private function quotePayload(LoanQuote $quote, bool $withSchedule = false): array
    {
        $calculation = $this->loanQuoteService->calculate($quote->toArray());

        $payload = [
            'id' => $quote->id,
            'client' => $quote->client ? [
                'id' => $quote->client->id,
                'full_name' => $quote->client->full_name,
                'identification' => $quote->client->identification,
            ] : null,
            'amount' => (float) $quote->amount,
            'interest_rate' => (float) $quote->interest_rate,
            'interest_type' => $quote->interest_type,
            'payment_frequency' => $quote->payment_frequency,
            'calculation_method' => $quote->calculation_method,
            'term_quantity' => (int) $quote->term_quantity,
            'status' => $quote->status,
            'start_date' => $quote->start_date?->toDateString(),
            'first_payment_date' => $quote->first_payment_date?->toDateString(),
            'created_at' => $quote->created_at?->toDateTimeString(),
            'installment_amount' => (float) $calculation['installment_amount'],
            'total_interest' => (float) $calculation['total_interest'],
            'total_amount' => (float) $calculation['total_amount'],
        ];

        if ($withSchedule) {
            $payload['installments'] = $calculation['installments'];
        }

        return $payload;
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
                'collector:id,name',
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
                'documents' => $this->loanDocumentPayloads($loanModel),
            ],
        ]);
    }

    public function loanDocuments(Request $request, int $loan): JsonResponse
    {
        $companyId = (int) $request->user()->company_id;
        $loanModel = Loan::query()
            ->forCompany($companyId)
            ->with('client:id,full_name')
            ->whereKey($loan)
            ->firstOrFail();

        return response()->json([
            'data' => $this->loanDocumentPayloads($loanModel),
        ]);
    }

    public function generateLoanDocument(Request $request, int $loan): JsonResponse
    {
        $companyId = (int) $request->user()->company_id;
        $validated = $request->validate([
            'document_type' => ['required', 'in:'.implode(',', $this->documentGenerationService->supportedLoanDocumentTypes())],
        ]);

        try {
            $document = $this->documentGenerationService->generateOrReuseLoanDocument(
                $companyId,
                $loan,
                $validated['document_type'],
                (int) $request->user()->id,
            );
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'data' => $this->documentPayload($document, true),
        ], 201);
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

        $loanId = $request->input('loan_id');
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
            'allocation_mode' => ['nullable', Rule::in(['auto', 'principal_and_interest', 'interest_only', 'principal_only'])],
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

    /**
     * @return array<int, array<string, mixed>>
     */
    private function loanDocumentPayloads(Loan $loan): array
    {
        return collect($this->documentGenerationService->supportedLoanDocumentTypes())
            ->filter(fn (string $type): bool => $type !== 'balance_letter' || $loan->status === 'paid')
            ->map(function (string $type) use ($loan): array {
                $document = Document::query()
                    ->where('company_id', $loan->company_id)
                    ->where('loan_id', $loan->id)
                    ->where('document_type', $type)
                    ->latest('id')
                    ->first();

                return $this->documentPayload($document, false, $type);
            })
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function documentPayload(?Document $document, bool $generated = false, ?string $expectedType = null): array
    {
        $documentType = $document?->document_type ?? $expectedType ?? 'document';

        return [
            'document_type' => $documentType,
            'label' => $this->documentLabel($documentType),
            'generated' => $document !== null,
            'document_id' => $document?->id,
            'title' => $document?->title,
            'download_url' => $document ? $this->documentShareService->publicDownloadUrl($document) : null,
            'created_at' => $document?->created_at?->toDateTimeString(),
            'just_generated' => $generated,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function collectorDetailPayload(Collector $collector): array
    {
        $commissions = $collector->relationLoaded('commissions') ? $collector->commissions : collect();
        $pending = $commissions->where('status', 'pending');

        return [
            'id' => $collector->id,
            'name' => $collector->name,
            'phone' => $collector->phone,
            'commission_type' => $collector->commission_type,
            'commission_base' => $collector->commission_base ?? 'payment_total',
            'commission_value' => (float) $collector->commission_value,
            'status' => $collector->status,
            'commission_summary' => [
                'total_generated' => (float) $commissions->sum('commission_amount'),
                'total_pending' => (float) $pending->sum('commission_amount'),
                'total_paid' => (float) $commissions->where('status', 'paid')->sum('commission_amount'),
            ],
            'pending_commissions' => $pending->map(fn (CollectorCommission $c): array => $this->commissionPayload($c))->values(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function commissionPayload(CollectorCommission $commission): array
    {
        return [
            'id' => $commission->id,
            'commission_type' => $commission->commission_type,
            'commission_value' => (float) $commission->commission_value,
            'base_amount' => (float) $commission->base_amount,
            'commission_amount' => (float) $commission->commission_amount,
            'status' => $commission->status,
            'paid_at' => $commission->paid_at?->toDateTimeString(),
            'receipt_number' => $commission->payment?->receipt_number,
        ];
    }

    private function documentLabel(string $documentType): string
    {
        return match ($documentType) {
            'promissory_note' => 'Pagare notarial',
            'loan_contract' => 'Contrato de prestamo',
            'disbursement_receipt' => 'Comprobante de desembolso',
            'payment_receipt' => 'Recibo de pago',
            'balance_letter' => 'Carta de saldo',
            'account_statement' => 'Estado de cuenta',
            default => 'Documento',
        };
    }
}
