<?php

declare(strict_types=1);

namespace App\Services\Contracts;

use App\Models\Contract;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;

class ContractShareService
{
    /**
     * Días de validez por defecto del enlace de firma cuando el contrato no
     * tiene una fecha de expiración propia.
     */
    private const DEFAULT_LINK_DAYS = 15;

    /**
     * Enlace público y firmado (token + expiración) para que el cliente abra y
     * firme el contrato desde su celular. Usa el uuid en la ruta, nunca el id.
     */
    public function signingUrl(Contract $contract): string
    {
        $expiration = $contract->expires_at instanceof Carbon
            ? $contract->expires_at
            : now()->addDays(self::DEFAULT_LINK_DAYS);

        return URL::temporarySignedRoute('contracts.sign', $expiration, ['uuid' => $contract->uuid]);
    }

    /**
     * Enlace de WhatsApp (wa.me) con el mensaje de invitación a firmar.
     */
    public function whatsappUrl(Contract $contract): ?string
    {
        $contract->loadMissing(['client', 'loan']);
        $phone = $contract->client?->phone ?: $contract->client?->secondary_phone;

        if (! $phone) {
            return null;
        }

        $message = implode("\n", [
            'Estimado/a '.($contract->client?->full_name ?: 'cliente').',',
            'Su contrato de préstamo '.($contract->loan?->loan_number ?? '').' está listo.',
            'Revíselo y fírmelo de forma segura desde su celular aquí:',
            $this->signingUrl($contract),
        ]);

        return 'https://wa.me/'.$this->normalizePhone($phone).'?text='.rawurlencode($message);
    }

    private function normalizePhone(string $phone): string
    {
        return preg_replace('/\D+/', '', $phone) ?? '';
    }
}
