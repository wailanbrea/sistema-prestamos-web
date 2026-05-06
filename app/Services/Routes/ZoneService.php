<?php

declare(strict_types=1);

namespace App\Services\Routes;

use App\Models\Zone;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class ZoneService
{
    /**
     * @return Collection<int, Zone>
     */
    public function listForCompany(int $companyId): Collection
    {
        return Zone::query()
            ->forCompany($companyId)
            ->withCount('routes')
            ->orderBy('name')
            ->get();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(int $companyId, array $data): Zone
    {
        $data['company_id'] = $companyId;

        return Zone::query()->create($data);
    }

    public function findForCompany(int $companyId, int $zoneId): Zone
    {
        return Zone::query()
            ->forCompany($companyId)
            ->whereKey($zoneId)
            ->firstOrFail();
    }

    public function delete(Zone $zone): void
    {
        if ($zone->routes()->exists()) {
            throw ValidationException::withMessages([
                'zone' => 'No se puede eliminar una zona con rutas vinculadas.',
            ]);
        }

        $zone->delete();
    }
}
