<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Api\V2\Concerns\BuildsApiPayloads;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Collector;
use App\Models\CollectorCommission;
use App\Models\CollectorRouteSession;
use App\Models\Document;
use App\Models\Loan;
use App\Models\LoanInstallment;
use App\Models\Payment;
use App\Models\Route as LendingRoute;
use App\Services\Documents\DocumentGenerationService;
use App\Services\Documents\DocumentShareService;
use App\Services\Payments\PaymentReceiptShareService;
use App\Services\Payments\PaymentService;
use App\Services\Routes\RouteTrackingService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class CollectorController extends Controller
{
    use BuildsApiPayloads {
        paymentPayload as basePaymentPayload;
    }

    public function __construct(
        private readonly PaymentService $paymentService,
        private readonly RouteTrackingService $routeTrackingService,
        private readonly PaymentReceiptShareService $receiptShareService,
        private readonly DocumentGenerationService $documentGenerationService,
        private readonly DocumentShareService $documentShareService,
    ) {
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
                'commissions' => [
                    'generated_total' => (float) CollectorCommission::query()
                        ->forCompany((int) $collector->company_id)
                        ->where('collector_id', $collector->id)
                        ->sum('commission_amount'),
                    'pending_total' => (float) CollectorCommission::query()
                        ->forCompany((int) $collector->company_id)
                        ->where('collector_id', $collector->id)
                        ->where('status', 'pending')
                        ->sum('commission_amount'),
                    'paid_total' => (float) CollectorCommission::query()
                        ->forCompany((int) $collector->company_id)
                        ->where('collector_id', $collector->id)
                        ->where('status', 'paid')
                        ->sum('commission_amount'),
                ],
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

    public function mapClients(Request $request): JsonResponse
    {
        $collector = $this->collectorForUser($request);

        $clients = $this->assignedClientQuery($collector)
            ->with(['routes' => fn ($query) => $query
                ->where('collector_id', $collector->id)
                ->orderBy('route_clients.order_number')
                ->select('routes.id', 'routes.name')])
            ->whereNotNull('address')
            ->orderBy('full_name')
            ->get();

        return response()->json([
            'data' => $clients
                ->map(fn (Client $client): array => [
                    ...$this->clientPayload($client),
                    'summary' => $this->clientFinancialSummary($collector, (int) $client->id),
                    'routes' => $client->routes
                        ->map(fn (LendingRoute $route): array => [
                            'id' => $route->id,
                            'name' => $route->name,
                            'order_number' => (int) $route->pivot->order_number,
                        ])
                        ->values(),
                ])
                ->values(),
        ]);
    }

    public function routes(Request $request): JsonResponse
    {
        $collector = $this->collectorForUser($request);

        $routes = LendingRoute::query()
            ->forCompany((int) $collector->company_id)
            ->where('collector_id', $collector->id)
            ->where('status', 'active')
            ->with(['zone:id,name', 'clients' => fn ($query) => $query
                ->orderBy('route_clients.order_number')
                ->select('clients.id', 'clients.code', 'clients.full_name', 'clients.phone', 'clients.address', 'clients.latitude', 'clients.longitude', 'clients.status', 'clients.risk_level')])
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $routes
                ->map(fn (LendingRoute $route): array => [
                    'id' => $route->id,
                    'name' => $route->name,
                    'description' => $route->description,
                    'zone' => $route->zone ? [
                        'id' => $route->zone->id,
                        'name' => $route->zone->name,
                    ] : null,
                    'clients_count' => $route->clients->count(),
                    'clients' => $route->clients
                        ->map(fn (Client $client): array => [
                            ...$this->clientPayload($client),
                            'order_number' => (int) $client->pivot->order_number,
                            'summary' => $this->clientFinancialSummary($collector, (int) $client->id),
                        ])
                        ->values(),
                ])
                ->values(),
        ]);
    }

    public function activeRouteSession(Request $request): JsonResponse
    {
        $collector = $this->collectorForUser($request);
        $session = $this->routeTrackingService->activeSessionForCollector($collector);

        return response()->json([
            'data' => $session ? $this->routeTrackingService->sessionPayload($session) : null,
        ]);
    }

    public function startRouteSession(Request $request): JsonResponse
    {
        $collector = $this->collectorForUser($request);
        $validated = $request->validate([
            'route_id' => [
                'required',
                'integer',
                Rule::exists('routes', 'id')
                    ->where('company_id', $collector->company_id)
                    ->where('collector_id', $collector->id)
                    ->where('status', 'active'),
            ],
        ]);

        $session = $this->routeTrackingService->startSession($collector, (int) $validated['route_id']);

        return response()->json([
            'data' => $this->routeTrackingService->sessionPayload($session),
        ], 201);
    }

    public function recordRouteLocation(Request $request, int $session): JsonResponse
    {
        $collector = $this->collectorForUser($request);
        $sessionModel = $this->routeSessionForCollector($collector, $session);
        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy_meters' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'battery_level' => ['nullable', 'integer', 'min:0', 'max:100'],
            'recorded_at' => ['nullable', 'date'],
        ]);

        try {
            $updated = $this->routeTrackingService->recordLocation(
                session: $sessionModel,
                latitude: (float) $validated['latitude'],
                longitude: (float) $validated['longitude'],
                accuracyMeters: isset($validated['accuracy_meters']) ? (int) $validated['accuracy_meters'] : null,
                batteryLevel: isset($validated['battery_level']) ? (int) $validated['battery_level'] : null,
                recordedAt: isset($validated['recorded_at']) ? CarbonImmutable::parse($validated['recorded_at']) : null,
            );
        } catch (InvalidArgumentException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'data' => $this->routeTrackingService->sessionPayload($updated),
        ]);
    }

    public function finishRouteSession(Request $request, int $session): JsonResponse
    {
        $collector = $this->collectorForUser($request);
        $sessionModel = $this->routeSessionForCollector($collector, $session);

        return response()->json([
            'data' => $this->routeTrackingService->sessionPayload($this->routeTrackingService->finishSession($sessionModel)),
        ]);
    }

    public function client(Request $request, int $client): JsonResponse
    {
        $collector = $this->collectorForUser($request);

        $clientModel = $this->assignedClientQuery($collector)
            ->with([
                'references:id,client_id,name,phone,relationship,address',
                'routes:id,name',
            ])
            ->whereKey($client)
            ->firstOrFail();

        $loans = $this->assignedLoanQuery($collector)
            ->with('client:id,code,full_name,identification,phone,address,status,risk_level')
            ->where('client_id', $clientModel->id)
            ->orderByRaw("case when status in ('active', 'late') then 0 else 1 end")
            ->orderByDesc('id')
            ->get();

        $installments = $this->assignedInstallmentQuery($collector)
            ->with('loan.client:id,code,full_name,identification,phone,address,status,risk_level')
            ->whereHas('loan', fn (Builder $query): Builder => $query->where('client_id', $clientModel->id))
            ->orderBy('due_date')
            ->limit(10)
            ->get();

        $payments = $this->assignedPaymentQuery($collector)
            ->with(['loan.client', 'collector', 'collectorCommission'])
            ->where('client_id', $clientModel->id)
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        return response()->json([
            'data' => [
                ...$this->clientDetailPayload($clientModel),
                'summary' => $this->clientFinancialSummary($collector, (int) $clientModel->id),
                'loans' => $loans->map(fn (Loan $loan): array => $this->loanPayload($loan))->values(),
                'pending_installments' => $installments->map(fn (LoanInstallment $installment): array => $this->installmentPayload($installment))->values(),
                'recent_payments' => $payments->map(fn (Payment $payment): array => $this->paymentPayload($payment))->values(),
            ],
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

    public function loan(Request $request, int $loan): JsonResponse
    {
        $collector = $this->collectorForUser($request);

        $loanModel = $this->assignedLoanQuery($collector)
            ->with([
                'client:id,code,full_name,identification,phone,address,status,risk_level',
                'installments' => fn ($query) => $query->orderBy('installment_number'),
                'payments' => fn ($query) => $query->with(['loan.client', 'collector', 'collectorCommission'])->orderByDesc('payment_date')->orderByDesc('id'),
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
        $collector = $this->collectorForUser($request);
        $loanModel = $this->assignedLoanQuery($collector)
            ->with('client:id,full_name')
            ->whereKey($loan)
            ->firstOrFail();

        return response()->json([
            'data' => $this->loanDocumentPayloads($loanModel),
        ]);
    }

    public function generateLoanDocument(Request $request, int $loan): JsonResponse
    {
        $collector = $this->collectorForUser($request);
        $validated = $request->validate([
            'document_type' => ['required', 'in:'.implode(',', $this->documentGenerationService->supportedLoanDocumentTypes())],
        ]);

        $loanModel = $this->assignedLoanQuery($collector)->whereKey($loan)->firstOrFail();

        try {
            $document = $this->documentGenerationService->generateOrReuseLoanDocument(
                (int) $collector->company_id,
                (int) $loanModel->id,
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

    public function installment(Request $request, int $installment): JsonResponse
    {
        $collector = $this->collectorForUser($request);

        $installmentModel = $this->assignedInstallmentDetailQuery($collector)
            ->whereKey($installment)
            ->firstOrFail();

        return response()->json([
            'data' => $this->installmentDetailPayload($installmentModel),
        ]);
    }

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
            'data' => $payments->through(fn (Payment $payment): array => $this->paymentPayload($payment))->items(),
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
            'data' => $this->paymentPayload($paymentModel, $paymentModel->status === 'valid'),
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
                    'data' => $this->paymentPayload($existingPayment),
                ]);
            }
        }

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
            'mobile_uuid' => ['nullable', 'uuid'],
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
            'data' => $this->paymentPayload($payment->fresh(['loan.client', 'collector', 'collectorCommission']) ?? $payment, true),
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

    private function routeSessionForCollector(Collector $collector, int $sessionId): CollectorRouteSession
    {
        return CollectorRouteSession::query()
            ->forCompany((int) $collector->company_id)
            ->where('collector_id', $collector->id)
            ->whereKey($sessionId)
            ->with(['route.clients', 'visitEvents.client'])
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
                    ->where('collector_id', $collector->id)
                    ->whereIn('status', ['active', 'late']);
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

        return [
            'active_loans' => (clone $loanQuery)->whereIn('status', ['active', 'late'])->count(),
            'late_loans' => (clone $loanQuery)->where('status', 'late')->count(),
            'total_principal' => (float) (clone $loanQuery)->sum('principal_amount'),
            'remaining_balance' => (float) (clone $loanQuery)->sum('remaining_balance'),
            'pending_installments' => (clone $pendingInstallmentQuery)->count(),
            'late_installments' => (clone $pendingInstallmentQuery)->where('status', 'late')->count(),
            'total_paid' => (float) (clone $paymentQuery)->sum('amount'),
            'last_payment_date' => (clone $paymentQuery)->max('payment_date'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function paymentPayload(Payment $payment, bool $includeShareData = false): array
    {
        $payload = $this->basePaymentPayload($payment);
        $payload['commission'] = $this->paymentCommissionPayload($payment);

        if ($includeShareData && $payment->status === 'valid') {
            $shareData = $this->receiptShareService->shareData($payment, (int) ($payment->created_by ?? 0));
            $payload['receipt_url'] = $shareData['receipt_url'];
            $payload['whatsapp_url'] = $shareData['whatsapp_url'];
        }

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
