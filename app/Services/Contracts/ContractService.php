<?php

declare(strict_types=1);

namespace App\Services\Contracts;

use App\Models\Contract;
use App\Models\ContractSignature;
use App\Models\Loan;
use App\Services\Audit\AuditService;
use App\Services\Documents\DocumentGenerationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class ContractService
{
    /**
     * Mapa de tipo de contrato => document_type soportado por DocumentGenerationService.
     *
     * @var array<string, string>
     */
    private const DOCUMENT_TYPE_MAP = [
        'loan_contract' => 'loan_contract',
        'promissory_note' => 'promissory_note',
        'disbursement_receipt' => 'disbursement_receipt',
        'settlement_letter' => 'balance_letter',
    ];

    public function __construct(
        private readonly DocumentGenerationService $documentGenerationService,
        private readonly AuditService $auditService,
    ) {
    }

    /**
     * Genera un contrato digital para un préstamo: crea el registro, renderiza el
     * PDF (con cláusulas, QR y hash de contenido) y deja el contrato en estado
     * `generated`, listo para enviarse a firmar.
     */
    public function generate(int $companyId, Loan $loan, string $contractType, int $userId): Contract
    {
        if (! array_key_exists($contractType, self::DOCUMENT_TYPE_MAP)) {
            throw new InvalidArgumentException('Tipo de contrato no soportado.');
        }

        $loan->loadMissing([
            'company.settings',
            'client',
            'collector',
            'installments' => fn ($query) => $query->orderBy('installment_number'),
        ]);

        return DB::transaction(function () use ($companyId, $loan, $contractType, $userId): Contract {
            $contract = Contract::query()->create([
                'company_id' => $companyId,
                'loan_id' => $loan->id,
                'client_id' => $loan->client_id,
                'contract_number' => $this->nextContractNumber($companyId, $loan->company?->settings?->contract_prefix ?? 'CON'),
                'contract_type' => $contractType,
                'status' => 'draft',
                'version' => 1,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            $this->renderVersion($loan, $contract, $userId, signature: null);

            $contract->forceFill([
                'status' => 'generated',
                'generated_at' => now(),
            ])->save();

            $this->logEvent($contract, 'generated', 'Contrato generado.');
            $this->audit('contract_generated', $companyId, $userId, $contract, "Contrato {$contract->contract_number} generado.");

            return $contract->fresh(['document', 'loan.client']) ?? $contract;
        });
    }

    /**
     * Regenera el PDF de un contrato no firmado (p. ej. tras editar el préstamo),
     * incrementando la versión. Un contrato firmado NO puede regenerarse.
     */
    public function regenerate(Contract $contract, int $userId): Contract
    {
        if ($contract->isSigned()) {
            throw new InvalidArgumentException('No se puede regenerar un contrato ya firmado.');
        }

        if (in_array($contract->status, ['cancelled', 'expired'], true)) {
            throw new InvalidArgumentException('No se puede regenerar un contrato anulado o vencido.');
        }

        $loan = $contract->loan()->with([
            'company.settings', 'client', 'collector',
            'installments' => fn ($query) => $query->orderBy('installment_number'),
        ])->firstOrFail();

        return DB::transaction(function () use ($contract, $loan, $userId): Contract {
            $contract->forceFill([
                'version' => $contract->version + 1,
                'status' => 'generated',
                'generated_at' => now(),
                'sent_at' => null,
                'viewed_at' => null,
                'updated_by' => $userId,
            ])->save();

            $this->renderVersion($loan, $contract, $userId, signature: null);

            $this->logEvent($contract, 'regenerated', "Contrato regenerado (v{$contract->version}).");
            $this->audit('contract_regenerated', (int) $contract->company_id, $userId, $contract, "Contrato {$contract->contract_number} regenerado.");

            return $contract->fresh(['document']) ?? $contract;
        });
    }

    /**
     * Renderiza el PDF de la versión actual del contrato y registra la versión.
     * Si se pasa una firma, la incrusta en el documento.
     */
    public function renderVersion(Loan $loan, Contract $contract, int $userId, ?ContractSignature $signature): void
    {
        $contentHash = $this->contentHash($loan, $contract);
        $verifyUrl = route('contracts.verify', ['uuid' => $contract->uuid]);

        $document = $this->documentGenerationService->renderContractDocument(
            companyId: (int) $contract->company_id,
            clientId: $loan->client_id,
            loanId: (int) $loan->id,
            documentType: self::DOCUMENT_TYPE_MAP[$contract->contract_type],
            title: $this->titleFor($contract),
            filenameKey: $contract->contract_number,
            view: 'documents.pdf.contract',
            data: [
                'loan' => $loan,
                'company' => $loan->company,
                'contract' => $contract,
                'contentHash' => $contentHash,
                'qrSvg' => $this->qrDataUri($verifyUrl),
                'verifyUrl' => $verifyUrl,
                'signature' => $signature,
                'signatureImage' => $signature ? $this->signatureDataUri($signature) : null,
                'generatedAt' => now(),
            ],
            createdBy: $userId,
        );

        $contract->forceFill([
            'document_id' => $document->id,
            'hash_sha256' => $contentHash,
        ])->save();

        $contract->versions()->create([
            'version' => $contract->version,
            'document_id' => $document->id,
            'pdf_path' => $document->file_path,
            'hash_sha256' => $contentHash,
            'created_at' => now(),
        ]);
    }

    /**
     * Registra la firma electrónica del cliente: guarda la imagen y las evidencias,
     * regenera el PDF con la firma incrustada, marca el contrato como `signed` y
     * actualiza el préstamo (contract_signed). Idempotente: no permite doble firma.
     *
     * @param array<string, mixed> $evidence
     */
    public function sign(Contract $contract, array $evidence): Contract
    {
        if ($contract->isSigned()) {
            throw new InvalidArgumentException('Este contrato ya fue firmado.');
        }

        if ($contract->isFinalized()) {
            throw new InvalidArgumentException('Este contrato ya no puede firmarse.');
        }

        $loan = $contract->loan()->with([
            'company.settings', 'client', 'collector',
            'installments' => fn ($query) => $query->orderBy('installment_number'),
        ])->firstOrFail();

        return DB::transaction(function () use ($contract, $loan, $evidence): Contract {
            $imagePath = $this->storeSignatureImage($contract, (string) $evidence['signature_image']);

            $signature = $contract->signatures()->create([
                'signature_image_path' => $imagePath,
                'signer_name' => $evidence['signer_name'],
                'ip_address' => $evidence['ip_address'] ?? null,
                'user_agent' => $evidence['user_agent'] ?? null,
                'device_type' => $evidence['device_type'] ?? null,
                'browser' => $evidence['browser'] ?? null,
                'platform' => $evidence['platform'] ?? null,
                'latitude' => $evidence['latitude'] ?? null,
                'longitude' => $evidence['longitude'] ?? null,
                'accepted_terms' => (bool) ($evidence['accepted_terms'] ?? false),
                'accepted_legal' => (bool) ($evidence['accepted_legal'] ?? false),
                'signed_at' => now(),
            ]);

            // Nueva versión firmada del documento.
            $contract->forceFill([
                'version' => $contract->version + 1,
                'status' => 'signed',
                'signed_at' => now(),
            ])->save();

            $this->renderVersion($loan, $contract, (int) ($contract->created_by ?? 0), $signature);

            $loan->forceFill([
                'contract_signed' => true,
                'contract_signed_at' => now(),
            ])->save();

            $this->logEvent($contract, 'signed', "Contrato firmado por {$signature->signer_name}.", [
                'ip' => $signature->ip_address,
                'device' => $signature->device_type,
            ]);
            $this->audit('contract_signed', (int) $contract->company_id, null, $contract, "Contrato {$contract->contract_number} firmado.");

            return $contract->fresh(['document', 'signatures']) ?? $contract;
        });
    }

    public function markSent(Contract $contract, int $userId): Contract
    {
        if ($contract->isFinalized()) {
            throw new InvalidArgumentException('El contrato ya no puede enviarse.');
        }

        $contract->forceFill([
            'status' => $contract->status === 'viewed' ? 'viewed' : 'sent',
            'sent_at' => $contract->sent_at ?? now(),
            'updated_by' => $userId,
        ])->save();

        $this->logEvent($contract, 'sent', 'Contrato enviado al cliente.');
        $this->audit('contract_sent', (int) $contract->company_id, $userId, $contract, "Contrato {$contract->contract_number} enviado.");

        return $contract;
    }

    public function markViewed(Contract $contract): Contract
    {
        if ($contract->viewed_at === null && ! $contract->isFinalized()) {
            $contract->forceFill([
                'status' => 'viewed',
                'viewed_at' => now(),
            ])->save();

            $this->logEvent($contract, 'viewed', 'Contrato visto por el cliente.');
        }

        return $contract;
    }

    public function cancel(Contract $contract, int $userId, ?string $reason = null): Contract
    {
        if ($contract->isSigned()) {
            throw new InvalidArgumentException('No se puede anular un contrato firmado.');
        }

        $contract->forceFill([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'updated_by' => $userId,
        ])->save();

        $this->logEvent($contract, 'cancelled', 'Contrato anulado.'.($reason ? " Motivo: {$reason}" : ''));
        $this->audit('contract_cancelled', (int) $contract->company_id, $userId, $contract, "Contrato {$contract->contract_number} anulado.");

        return $contract;
    }

    public function logEvent(Contract $contract, string $eventType, ?string $description = null, array $metadata = []): void
    {
        $contract->events()->create([
            'event_type' => $eventType,
            'description' => $description,
            'ip_address' => request()?->ip(),
            'metadata_json' => $metadata ?: null,
            'created_at' => now(),
        ]);
    }

    private function audit(string $action, int $companyId, ?int $userId, Contract $contract, string $description): void
    {
        $this->auditService->record(
            action: $action,
            module: 'contracts',
            companyId: $companyId,
            userId: $userId,
            auditable: $contract,
            description: $description,
            newValues: $contract->only(['id', 'contract_number', 'status', 'version']),
        );
    }

    /**
     * Hash de integridad del contenido del contrato. Es determinista a partir de
     * los datos clave del préstamo, de modo que puede imprimirse en el PDF y
     * recalcularse en la página de verificación.
     */
    public function contentHash(Loan $loan, Contract $contract): string
    {
        $canonical = implode('|', [
            $contract->contract_number,
            $loan->loan_number,
            number_format((float) $loan->principal_amount, 2, '.', ''),
            number_format((float) $loan->total_amount, 2, '.', ''),
            (string) ($loan->client->identification ?? ''),
            (string) ($loan->client->full_name ?? ''),
            (string) $loan->term_quantity,
            (string) $contract->version,
        ]);

        return hash('sha256', $canonical);
    }

    private function qrDataUri(string $content): string
    {
        $svg = QrCode::format('svg')->size(130)->margin(0)->errorCorrection('M')->generate($content);

        return 'data:image/svg+xml;base64,'.base64_encode((string) $svg);
    }

    /**
     * Decodifica la firma (data URL PNG) y la guarda en el disco privado.
     */
    private function storeSignatureImage(Contract $contract, string $dataUrl): string
    {
        $base64 = $dataUrl;
        if (str_contains($dataUrl, ',')) {
            $base64 = substr($dataUrl, strpos($dataUrl, ',') + 1);
        }

        $binary = base64_decode($base64, true);
        if ($binary === false) {
            throw new InvalidArgumentException('La firma no es una imagen válida.');
        }

        $path = "contracts/signatures/{$contract->uuid}-".now()->format('YmdHis').'.png';
        Storage::disk('local')->put($path, $binary);

        return $path;
    }

    private function signatureDataUri(ContractSignature $signature): ?string
    {
        if (! $signature->signature_image_path || ! Storage::disk('local')->exists($signature->signature_image_path)) {
            return null;
        }

        return 'data:image/png;base64,'.base64_encode((string) Storage::disk('local')->get($signature->signature_image_path));
    }

    private function titleFor(Contract $contract): string
    {
        return match ($contract->contract_type) {
            'promissory_note' => "Pagaré {$contract->contract_number}",
            'disbursement_receipt' => "Comprobante de desembolso {$contract->contract_number}",
            'settlement_letter' => "Carta de saldo {$contract->contract_number}",
            default => "Contrato de préstamo {$contract->contract_number}",
        };
    }

    private function nextContractNumber(int $companyId, string $prefix): string
    {
        $nextId = (int) Contract::query()->forCompany($companyId)->count() + 1;

        return $prefix.'-'.now()->format('Ymd').'-'.str_pad((string) $nextId, 5, '0', STR_PAD_LEFT);
    }
}
