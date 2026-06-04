<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Loan;
use App\Models\Payment;
use App\Services\Documents\DocumentGenerationService;
use App\Services\Documents\DocumentService;
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
    ) {
    }

    public function index(Request $request): View
    {
        $companyId = (int) $request->user()->company_id;
        $filters = $request->only(['search', 'document_type']);

        return view('documents.index', [
            'documents' => $this->documentService->paginateForCompany($companyId, $filters),
            'loans' => Loan::query()
                ->with('client:id,full_name')
                ->forCompany($companyId)
                ->latest('id')
                ->limit(100)
                ->get(['id', 'client_id', 'loan_number', 'status']),
            'payments' => Payment::query()
                ->with('client:id,full_name')
                ->forCompany($companyId)
                ->latest('id')
                ->limit(100)
                ->get(['id', 'client_id', 'receipt_number', 'amount']),
            'filters' => $filters,
        ]);
    }

    public function generateLoanDocument(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->can('documents.generate'), 403);

        $validated = $request->validate([
            'loan_id' => ['required', 'integer'],
            'document_type' => ['required', 'in:promissory_note,disbursement_receipt,balance_letter'],
        ]);

        try {
            $document = $this->documentGenerationService->generateForLoan(
                (int) $request->user()->company_id,
                (int) $validated['loan_id'],
                $validated['document_type'],
                (int) $request->user()->id,
            );
        } catch (InvalidArgumentException $exception) {
            return back()->withInput()->withErrors(['document_type' => $exception->getMessage()]);
        }

        return redirect()
            ->route('documents.download', $document)
            ->with('status', 'Documento generado correctamente.');
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
            ->route('documents.download', $document)
            ->with('status', 'Recibo generado correctamente.');
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
        $model = Document::query()
            ->whereKey($document)
            ->where('document_type', 'payment_receipt')
            ->firstOrFail();
        abort_unless(Storage::disk('local')->exists($model->file_path), 404);

        return Storage::disk('local')->download($model->file_path, basename($model->file_path), [
            'Content-Type' => 'application/pdf',
        ]);
    }
}
