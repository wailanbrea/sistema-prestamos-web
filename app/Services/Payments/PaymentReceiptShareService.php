<?php

declare(strict_types=1);

namespace App\Services\Payments;

use App\Models\Document;
use App\Models\Payment;
use App\Services\Documents\DocumentGenerationService;
use Illuminate\Support\Facades\URL;

class PaymentReceiptShareService
{
    public function __construct(
        private readonly DocumentGenerationService $documentGenerationService,
    ) {
    }

    /**
     * @return array{receipt_url:string, whatsapp_url:?string, phone:?string}
     */
    public function shareData(Payment $payment, ?int $createdBy = null): array
    {
        $payment->loadMissing(['client', 'loan']);

        $document = $this->receiptDocument($payment, $createdBy);
        $receiptUrl = $this->receiptUrl($document);
        $phone = $payment->client?->phone ?: $payment->client?->secondary_phone;

        return [
            'receipt_url' => $receiptUrl,
            'whatsapp_url' => $phone ? $this->whatsAppUrl($payment, $phone, $receiptUrl) : null,
            'phone' => $phone,
        ];
    }

    public function receiptDocument(Payment $payment, ?int $createdBy = null): Document
    {
        $payment->loadMissing(['client', 'loan']);

        $title = "Recibo de pago {$payment->receipt_number}";
        $existing = Document::query()
            ->where('company_id', $payment->company_id)
            ->where('client_id', $payment->client_id)
            ->where('loan_id', $payment->loan_id)
            ->where('document_type', 'payment_receipt')
            ->where('title', $title)
            ->latest('id')
            ->first();

        if ($existing) {
            return $existing;
        }

        return $this->documentGenerationService->generatePaymentReceipt(
            (int) $payment->company_id,
            (int) $payment->id,
            $createdBy ?? (int) ($payment->created_by ?? 0),
        );
    }

    public function receiptUrl(Document $document): string
    {
        return URL::temporarySignedRoute(
            'documents.public-download',
            now()->addDays(30),
            ['document' => $document->id],
        );
    }

    private function whatsAppUrl(Payment $payment, string $phone, string $receiptUrl): string
    {
        $message = implode("\n", [
            'Hola '.($payment->client?->full_name ?: 'cliente').',',
            'Hemos registrado tu pago correctamente.',
            'Recibo: '.$payment->receipt_number,
            'Prestamo: '.($payment->loan?->loan_number ?: 'N/D'),
            'Monto: '.currency().' '.number_format((float) $payment->amount, 2),
            'Fecha: '.$payment->payment_date?->format('d/m/Y'),
            'Ver recibo: '.$receiptUrl,
        ]);

        return 'https://wa.me/'.$this->normalizePhone($phone).'?text='.rawurlencode($message);
    }

    private function normalizePhone(string $phone): string
    {
        return preg_replace('/\D+/', '', $phone) ?? '';
    }
}
