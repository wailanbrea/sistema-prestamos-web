<?php

declare(strict_types=1);

namespace App\Services\Documents;

use App\Models\Document;
use App\Models\Loan;
use App\Models\Payment;
use App\Services\Audit\AuditService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

class DocumentGenerationService
{
    public function __construct(private readonly AuditService $auditService)
    {
    }

    public function generateForLoan(int $companyId, int $loanId, string $documentType, int $createdBy): Document
    {
        if (! in_array($documentType, ['promissory_note', 'disbursement_receipt', 'balance_letter'], true)) {
            throw new InvalidArgumentException('Tipo de documento de préstamo no soportado.');
        }

        $loan = Loan::query()
            ->with(['company.settings', 'client', 'collector', 'installments'])
            ->forCompany($companyId)
            ->whereKey($loanId)
            ->firstOrFail();

        if ($documentType === 'balance_letter' && $loan->status !== 'paid') {
            throw new InvalidArgumentException('La carta de saldo solo puede generarse para préstamos saldados.');
        }

        $title = $this->titleFor($documentType, $loan->loan_number);
        $path = $this->renderAndStore(
            view: "documents.pdf.{$documentType}",
            data: ['loan' => $loan, 'company' => $loan->company, 'generatedAt' => now()],
            companyId: $companyId,
            documentType: $documentType,
            filenameKey: $loan->loan_number,
        );

        return $this->storeDocument($companyId, $loan->client_id, $loan->id, $documentType, $title, $path, $createdBy);
    }

    public function generatePaymentReceipt(int $companyId, int $paymentId, int $createdBy): Document
    {
        $payment = Payment::query()
            ->with(['loan.company.settings', 'loan.client', 'collector', 'details.installment'])
            ->forCompany($companyId)
            ->whereKey($paymentId)
            ->firstOrFail();

        $title = "Recibo de pago {$payment->receipt_number}";
        $path = $this->renderAndStore(
            view: 'documents.pdf.payment_receipt',
            data: ['payment' => $payment, 'company' => $payment->loan->company, 'generatedAt' => now()],
            companyId: $companyId,
            documentType: 'payment_receipt',
            filenameKey: $payment->receipt_number,
        );

        return $this->storeDocument($companyId, $payment->client_id, $payment->loan_id, 'payment_receipt', $title, $path, $createdBy);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function renderAndStore(string $view, array $data, int $companyId, string $documentType, string $filenameKey): string
    {
        $directory = "documents/companies/{$companyId}/{$documentType}";
        $filename = Str::slug($filenameKey).'-'.now()->format('YmdHis').'-'.Str::lower(Str::random(6)).'.pdf';
        $path = "{$directory}/{$filename}";

        Storage::disk('local')->put($path, Pdf::loadView($view, $data)->output());

        return $path;
    }

    private function storeDocument(
        int $companyId,
        ?int $clientId,
        ?int $loanId,
        string $documentType,
        string $title,
        string $path,
        int $createdBy,
    ): Document {
        $document = Document::query()->create([
            'company_id' => $companyId,
            'client_id' => $clientId,
            'loan_id' => $loanId,
            'document_type' => $documentType,
            'title' => $title,
            'file_path' => $path,
            'created_by' => $createdBy,
        ]);

        $this->auditService->record(
            action: 'document_generated',
            module: 'documents',
            companyId: $companyId,
            userId: $createdBy,
            auditable: $document,
            description: "Documento generado: {$title}.",
            newValues: $document->toArray(),
        );

        return $document;
    }

    private function titleFor(string $documentType, string $loanNumber): string
    {
        return match ($documentType) {
            'promissory_note' => "Pagaré notarial {$loanNumber}",
            'disbursement_receipt' => "Comprobante de desembolso {$loanNumber}",
            'balance_letter' => "Carta de saldo {$loanNumber}",
            default => "Documento {$loanNumber}",
        };
    }
}
