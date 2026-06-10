<?php

declare(strict_types=1);

namespace App\Services\Clients;

use App\Models\Client;
use App\Models\ClientRegistrationLink;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class ClientRegistrationLinkService
{
    public function __construct(
        private readonly ClientService $clientService,
        private readonly ClientDocumentService $clientDocumentService,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(int $companyId, array $data, ?int $createdBy = null): ClientRegistrationLink
    {
        return DB::transaction(function () use ($companyId, $data, $createdBy): ClientRegistrationLink {
            $phone = isset($data['recipient_phone']) && trim((string) $data['recipient_phone']) !== ''
                ? trim((string) $data['recipient_phone'])
                : null;

            if ($phone !== null) {
                ClientRegistrationLink::query()
                    ->where('company_id', $companyId)
                    ->where('recipient_phone', $phone)
                    ->whereNull('used_at')
                    ->whereNull('revoked_at')
                    ->update(['revoked_at' => now()]);
            }

            return ClientRegistrationLink::query()->create([
                'company_id' => $companyId,
                'created_by' => $createdBy,
                'token' => Str::random(64),
                'recipient_name' => $data['recipient_name'] ?? null,
                'recipient_phone' => $phone,
            ]);
        });
    }

    /**
     * @return Collection<int, ClientRegistrationLink>
     */
    public function latestForCompany(int $companyId): Collection
    {
        return ClientRegistrationLink::query()
            ->with(['createdBy:id,name', 'usedClient:id,full_name,phone'])
            ->forCompany($companyId)
            ->latest('id')
            ->limit(20)
            ->get();
    }

    public function findAvailableByToken(string $token): ClientRegistrationLink
    {
        $link = ClientRegistrationLink::query()
            ->where('token', $token)
            ->firstOrFail();

        if (! $link->isAvailable()) {
            throw new InvalidArgumentException('Este enlace ya fue utilizado o ya no esta disponible.');
        }

        return $link;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function registerClientFromLink(ClientRegistrationLink $link, array $data): Client
    {
        if (! $link->isAvailable()) {
            throw new InvalidArgumentException('Este enlace ya fue utilizado o ya no esta disponible.');
        }

        if (! empty($data['code']) && Client::query()->where('company_id', $link->company_id)->where('code', $data['code'])->exists()) {
            throw new InvalidArgumentException('El codigo ya existe en esta empresa.');
        }

        return DB::transaction(function () use ($link, $data): Client {
            $clientData = $data;
            unset($clientData['id_front'], $clientData['id_back']);

            $client = $this->clientService->create($link->company_id, [
                ...$clientData,
                'phone' => $clientData['phone'] ?? $link->recipient_phone,
                'status' => 'active',
                'risk_level' => 'low',
            ]);

            $this->clientDocumentService->storeIdentityDocuments($client, [
                'front' => $data['id_front'],
                'back' => $data['id_back'],
            ]);

            $link->forceFill([
                'used_at' => now(),
                'used_client_id' => $client->id,
            ])->save();

            return $client;
        });
    }
}
