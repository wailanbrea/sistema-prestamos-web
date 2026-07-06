<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Api\V2\Concerns\BuildsApiPayloads;
use App\Http\Controllers\Controller;
use App\Http\Requests\Loans\StoreLoanRequest;
use App\Http\Requests\Loans\UpdateLoanRequest;
use App\Models\Contract;
use App\Models\Document;
use App\Models\Loan;
use App\Models\LoanInstallment;
use App\Models\Payment;
use App\Services\Contracts\ContractService;
use App\Services\Contracts\ContractShareService;
use App\Services\Documents\DocumentGenerationService;
use App\Services\Documents\DocumentShareService;
use App\Services\Loans\LoanService;
use App\Services\Notifications\EventNotifier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class AdminLoanController extends Controller
{
    use BuildsApiPayloads;

    public function __construct(
        private readonly LoanService $loanService,
        private readonly DocumentGenerationService $documentGenerationService,
        private readonly DocumentShareService $documentShareService,
        private readonly ContractService $contractService,
        private readonly ContractShareService $contractShareService,
        private readonly EventNotifier $notifier,
    ) {}

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

    public function loans(Request $request): JsonResponse
    {
        $companyId = (int) $request->user()->company_id;

        $validated = $request->validate([
            'status' => ['nullable', Rule::in(['active', 'late', 'paid', 'refinanced', 'cancelled', 'legal', 'written_off', 'pending'])],
            'collector_id' => ['nullable', 'integer'],
            'search' => ['nullable', 'string', 'max:120'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'include_paid' => ['nullable', 'boolean'],
        ]);
        $includePaid = (bool) ($validated['include_paid'] ?? false);

        $loans = Loan::query()
            ->forCompany($companyId)
            ->withDueSummary()
            ->with('client:id,code,full_name,identification,phone,address,status,risk_level')
            ->when($validated['status'] ?? null, fn (Builder $query, string $status): Builder => $query->where('status', $status))
            ->when(empty($validated['status']) && ! $includePaid, fn (Builder $query): Builder => $query->whereIn('status', ['active', 'late']))
            ->when($validated['collector_id'] ?? null, fn (Builder $query, int $collectorId): Builder => $query->where('collector_id', $collectorId))
            ->when($validated['search'] ?? null, fn (Builder $query, string $search): Builder => $query
                ->where(fn (Builder $inner) => $inner
                    ->where('loan_number', 'like', "%{$search}%")
                    ->orWhereHas('client', fn (Builder $c) => $c
                        ->where('full_name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('identification', 'like', "%{$search}%"))))
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

    public function waiveInstallmentLateFee(Request $request, int $loan, int $installment): JsonResponse
    {
        $companyId = (int) $request->user()->company_id;

        $loanModel = Loan::query()
            ->forCompany($companyId)
            ->whereKey($loan)
            ->firstOrFail();

        $installmentModel = LoanInstallment::query()
            ->where('loan_id', $loanModel->id)
            ->whereKey($installment)
            ->firstOrFail();

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $updated = $this->loanService->waiveInstallmentLateFee(
                companyId: $companyId,
                userId: $request->user()?->id,
                loan: $loanModel,
                installment: $installmentModel,
                reason: $validated['reason'] ?? 'Eliminada desde Android',
            );
        } catch (InvalidArgumentException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'errors' => [
                    'late_fee' => [$exception->getMessage()],
                ],
            ], 422);
        }

        $updated->loadMissing('loan.client:id,full_name,identification,phone,address');

        return response()->json([
            'data' => $this->installmentPayload($updated),
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

    /**
     * Devuelve el contrato digital más reciente de un préstamo (o null), con los
     * enlaces para firmar/compartir. Requiere permiso legal.manage (en la ruta).
     */
    public function loanContract(Request $request, int $loan): JsonResponse
    {
        $companyId = (int) $request->user()->company_id;
        $loanModel = Loan::query()->forCompany($companyId)->whereKey($loan)->firstOrFail();

        $contract = Contract::query()
            ->forCompany($companyId)
            ->where('loan_id', $loanModel->id)
            ->latest('id')
            ->first();

        return response()->json([
            'data' => $contract ? $this->contractPayload($contract) : null,
        ]);
    }

    /**
     * Genera un contrato digital para el préstamo y devuelve los enlaces de firma.
     */
    public function generateLoanContract(Request $request, int $loan): JsonResponse
    {
        $companyId = (int) $request->user()->company_id;
        $loanModel = Loan::query()->forCompany($companyId)->whereKey($loan)->firstOrFail();

        $validated = $request->validate([
            'contract_type' => ['nullable', Rule::in(['loan_contract', 'promissory_note', 'disbursement_receipt', 'settlement_letter'])],
        ]);

        try {
            $contract = $this->contractService->generate(
                $companyId,
                $loanModel,
                $validated['contract_type'] ?? 'loan_contract',
                (int) $request->user()->id,
            );
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'data' => $this->contractPayload($contract),
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
     * @return array<string, mixed>
     */
    private function contractPayload(Contract $contract): array
    {
        return [
            'uuid' => $contract->uuid,
            'contract_number' => $contract->contract_number,
            'status' => $contract->status,
            'version' => $contract->version,
            'signed_at' => $contract->signed_at?->toIso8601String(),
            'signing_url' => $contract->isFinalized() ? null : $this->contractShareService->signingUrl($contract),
            'whatsapp_url' => $contract->isFinalized() ? null : $this->contractShareService->whatsappUrl($contract),
            'verify_url' => route('contracts.verify', ['uuid' => $contract->uuid]),
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
            'whatsapp_url' => $document ? $this->documentShareService->whatsAppUrl($document) : null,
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
