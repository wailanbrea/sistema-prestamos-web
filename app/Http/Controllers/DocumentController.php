<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Loan;
use App\Models\Payment;
use App\Services\Documents\DocumentGenerationService;
use App\Services\Documents\DocumentService;
use App\Services\Documents\DocumentShareService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function __construct(
        private readonly DocumentService $documentService,
        private readonly DocumentGenerationService $documentGenerationService,
        private readonly DocumentShareService $documentShareService,
    ) {
    }

    public function index(Request $request): View
    {
        $companyId = (int) $request->user()->company_id;
        $filters = $request->only(['search', 'document_type']);

        return view('documents.index', [
            'documents' => $this->documentService->paginateForCompany($companyId, $filters),
            'loanDocumentTypes' => $this->documentGenerationService->supportedLoanDocumentTypes(),
            'loans' => Loan::query()
                ->with('client:id,full_name')
                ->forCompany($companyId)
                ->orderBy('client_id')
                ->orderByDesc('id')
                ->limit(200)
                ->get(['id', 'client_id', 'loan_number', 'status'])
                ->values(),
            'payments' => Payment::query()
                ->with('client:id,full_name')
                ->forCompany($companyId)
                ->orderBy('client_id')
                ->orderByDesc('payment_date')
                ->latest('id')
                ->limit(200)
                ->get(['id', 'client_id', 'receipt_number', 'amount', 'payment_date'])
                ->values(),
            'filters' => $filters,
        ]);
    }

    public function generateLoanDocument(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->can('documents.generate'), 403);

        $validated = $request->validate([
            'loan_id' => ['required', 'integer'],
            'document_type' => ['required', 'in:'.implode(',', $this->documentGenerationService->supportedLoanDocumentTypes())],
        ]);

        try {
            $document = $this->documentGenerationService->generateOrReuseLoanDocument(
                (int) $request->user()->company_id,
                (int) $validated['loan_id'],
                $validated['document_type'],
                (int) $request->user()->id,
            );
        } catch (InvalidArgumentException $exception) {
            return back()->withInput()->withErrors(['document_type' => $exception->getMessage()]);
        }

        return redirect()
            ->route('documents.index')
            ->with('status', 'Documento generado correctamente.')
            ->with('generatedDocumentId', $document->id);
    }

    public function generateLoanDocumentForLoan(Request $request, int $loan): RedirectResponse
    {
        abort_unless($request->user()?->can('documents.generate'), 403);

        $validated = $request->validate([
            'document_type' => ['required', 'in:'.implode(',', $this->documentGenerationService->supportedLoanDocumentTypes())],
        ]);

        try {
            $document = $this->documentGenerationService->generateOrReuseLoanDocument(
                (int) $request->user()->company_id,
                $loan,
                $validated['document_type'],
                (int) $request->user()->id,
            );
        } catch (InvalidArgumentException $exception) {
            return redirect()
                ->route('loans.show', $loan)
                ->withErrors(['loan_document' => $exception->getMessage()]);
        }

        return redirect()
            ->route('loans.show', $loan)
            ->with('status', 'Documento generado correctamente.')
            ->with('generatedDocumentId', $document->id);
    }

    public function generatePaymentReceipt(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->can('documents.generate'), 403);

        $validated = $request->validate([
            'payment_id' => ['required', 'integer'],
        ]);

        $document = $this->documentGenerationService->generatePaymentReceipt(
            (int) $request->user()->company_id,
            (int) $validated['payment_id'],
            (int) $request->user()->id,
        );

        return redirect()
            ->route('documents.index')
            ->with('status', 'Recibo generado correctamente.')
            ->with('generatedDocumentId', $document->id);
    }

    public function openWhatsapp(Request $request, int $document): RedirectResponse
    {
        abort_unless($request->user()?->can('documents.generate'), 403);

        $model = $this->documentService->findForCompany(
            (int) $request->user()->company_id,
            $document,
        );
        $whatsAppUrl = $this->documentShareService->whatsAppUrl($model);

        return redirect()->away($whatsAppUrl);
    }

    public function download(Request $request, int $document): StreamedResponse
    {
        abort_unless($request->user()?->can('documents.generate'), 403);

        $model = $this->documentService->findForCompany((int) $request->user()->company_id, $document);
        abort_unless(Storage::disk('local')->exists($model->file_path), 404);

        return Storage::disk('local')->download($model->file_path, basename($model->file_path), [
            'Content-Type' => 'application/pdf',
        ]);
    }

    public function publicDownload(int $document): StreamedResponse
    {
        $model = Document::query()->whereKey($document)->firstOrFail();
        abort_unless(Storage::disk('local')->exists($model->file_path), 404);

        return Storage::disk('local')->download($model->file_path, basename($model->file_path), [
            'Content-Type' => 'application/pdf',
        ]);
    }
}
