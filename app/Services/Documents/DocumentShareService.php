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
}
