<?php

declare(strict_types=1);

namespace App\Services\Tenancy;

use App\Models\Company;
use Illuminate\Contracts\Auth\Authenticatable;
use RuntimeException;

class CurrentCompany
{
    public function id(?Authenticatable $user = null): int
    {
        $user ??= auth()->user();

        if (! $user?->company_id) {
            throw new RuntimeException('No hay empresa activa para el usuario actual.');
        }

        return (int) $user->company_id;
    }

    public function get(?Authenticatable $user = null): Company
    {
        $user ??= auth()->user();

        if (! $user?->company) {
            throw new RuntimeException('No hay empresa activa para el usuario actual.');
        }

        return $user->company;
    }
}
