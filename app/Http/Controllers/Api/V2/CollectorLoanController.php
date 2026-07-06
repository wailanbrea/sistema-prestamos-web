<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Api\V2\Concerns\BuildsApiPayloads;
use App\Http\Controllers\Api\V2\Concerns\InteractsWithCollectorPortfolio;
use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\Loan;
use App\Models\LoanInstallment;
use App\Models\Payment;
use App\Services\Documents\DocumentGenerationService;
use App\Services\Documents\DocumentShareService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class CollectorLoanController extends Controller
{
    use BuildsApiPayloads;
    use InteractsWithCollectorPortfolio;

    public function __construct(
        private readonly DocumentGenerationService $documentGenerationService,
        private readonly DocumentShareService $documentShareService,
    ) {}

    public function loans(Request $request): JsonResponse
    {
        $collector = $this->collectorForUser($request);
        $validated = $request->validate([
            'include_paid' => ['nullable', 'boolean'],
            'search' => ['nullable', 'string', 'max:120'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $includePaid = (bool) ($validated['include_paid'] ?? false);

        $loans = $this->assignedLoanQuery($collector)
            ->withDueSummary()
            ->with('client:id,full_name,identification,phone,address')
            ->when(! $includePaid, fn (Builder $query): Builder => $query->where(function (Builder $query): void {
                $query->whereIn('status', ['active', 'late'])
                    ->orWhereHas('installments', fn (Builder $installmentQuery): Builder => $installmentQuery
                        ->whereIn('status', ['pending', 'partial', 'late']));
            }))
            ->when($validated['search'] ?? null, fn (Builder $query, string $search): Builder => $query
                ->where(fn (Builder $inner) => $inner
                    ->where('loan_number', 'like', "%{$search}%")
                    ->orWhereHas('client', fn (Builder $c) => $c
                        ->where('full_name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('identification', 'like', "%{$search}%"))))
            ->orderBy('loan_number')
            ->paginate((int) ($validated['per_page'] ?? 25));

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
                    ->map(fn (Payment $payment): array => $this->collectorPaymentPayload($payment))
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
