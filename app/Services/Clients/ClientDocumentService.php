<?php

declare(strict_types=1);

namespace App\Services\Clients;

use App\Models\Client;
use App\Models\ClientDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ClientDocumentService
{
    /**
     * @param array{front:UploadedFile,back:UploadedFile} $files
     */
    public function storeIdentityDocuments(Client $client, array $files): void
    {
        $paths = [];

        try {
            foreach ([
                'front' => ['type' => 'identity_front', 'title' => 'ID frontal'],
                'back' => ['type' => 'identity_back', 'title' => 'ID reverso'],
            ] as $side => $meta) {
                $file = $files[$side];
                $path = $this->storePrivateFile($client, $file, $side);
                $paths[] = $path;

                ClientDocument::query()->create([
                    'client_id' => $client->id,
                    'document_type' => $meta['type'],
                    'title' => $meta['title'],
                    'file_path' => $path,
                ]);
            }
        } catch (\Throwable $exception) {
            foreach ($paths as $path) {
                Storage::disk('local')->delete($path);
            }

            throw $exception;
        }
    }

    public function findForClient(int $companyId, int $clientId, int $documentId): ClientDocument
    {
        return ClientDocument::query()
            ->whereKey($documentId)
            ->whereHas('client', function ($query) use ($companyId, $clientId): void {
                $query->where('company_id', $companyId)
                    ->whereKey($clientId);
            })
            ->firstOrFail();
    }

    public function exists(ClientDocument $document): bool
    {
        return Storage::disk('local')->exists($document->file_path);
    }

    private function storePrivateFile(Client $client, UploadedFile $file, string $side): string
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'jpg');
        $directory = "client-documents/company-{$client->company_id}/client-{$client->id}/identity";
        $filename = "{$side}-".Str::lower((string) Str::uuid()).".{$extension}";

        return $file->storeAs($directory, $filename, 'local');
    }
}
