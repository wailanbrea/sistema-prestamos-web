<?php

declare(strict_types=1);

use App\Models\CompanySetting;
use Illuminate\Support\Facades\Auth;

if (! function_exists('company_setting')) {
    /**
     * Valor de un ajuste de la empresa autenticada (cacheado por request).
     */
    function company_setting(string $key, mixed $default = null): mixed
    {
        static $cache = [];

        $companyId = (int) (Auth::user()?->company_id ?? 0);

        if (! array_key_exists($companyId, $cache)) {
            $cache[$companyId] = $companyId > 0
                ? (CompanySetting::query()->where('company_id', $companyId)->first()?->toArray() ?? [])
                : [];
        }

        $value = $cache[$companyId][$key] ?? null;

        return ($value === null || $value === '') ? $default : $value;
    }
}

if (! function_exists('currency')) {
    /**
     * Símbolo de moneda de la empresa autenticada. Fallback: RD$.
     */
    function currency(): string
    {
        return (string) company_setting('currency', 'RD$');
    }
}
