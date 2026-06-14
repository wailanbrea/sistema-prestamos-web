<?php

declare(strict_types=1);

namespace App\Services\Documents;

use App\Models\Document;
use Illuminate\Support\Facades\URL;

class DocumentShareService
{
    public function publicDownloadUrl(Document $document, int $days = 30): string
    {
        return URL::temporarySignedRoute(
            'documents.public-download',
            now()->addDays($days),
            ['document' => $document->id],
        );
    }

    public function whatsAppUrl(Document $document): string
    {
        $document->loadMissing(['client', 'loan']);

        $phone = $this->normalizePhone(
            (string) ($document->client?->phone ?: $document->client?->secondary_phone),
        );

        $loanReference = $document->loan?->loan_number
            ? " del prestamo {$document->loan->loan_number}"
            : '';

        $message = sprintf(
            "Hola %s, le compartimos %s%s:\n%s",
            $document->client?->full_name ?: 'cliente',
            $document->title,
            $loanReference,
            $this->publicDownloadUrl($document),
        );

        $recipient = $phone === '' ? '' : $phone;

        return 'https://wa.me/'.$recipient.'?text='.rawurlencode($message);
    }

    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (strlen($digits) === 10) {
            return '1'.$digits;
        }

        return $digits;
    }
}
