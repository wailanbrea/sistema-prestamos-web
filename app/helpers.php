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

if (! function_exists('loan_default_currency')) {
    /**
     * Moneda por defecto al crear prestamos.
     */
    function loan_default_currency(): string
    {
        return (string) company_setting('default_loan_currency', currency());
    }
}

if (! function_exists('account_payable_default_currency')) {
    /**
     * Moneda por defecto al crear cuentas por pagar.
     */
    function account_payable_default_currency(): string
    {
        return (string) company_setting('default_account_payable_currency', loan_default_currency());
    }
}

if (! function_exists('money_symbol')) {
    /**
     * Simbolo de moneda puntual. Fallback: moneda general de la empresa.
     */
    function money_symbol(?string $currency = null): string
    {
        return (string) ($currency ?: currency());
    }
}

if (! function_exists('default_map_center')) {
    /**
     * Centro inicial para mapas de la empresa autenticada.
     *
     * @return array{lat: float, lng: float, address: string}
     */
    function default_map_center(): array
    {
        $lat = (float) company_setting('default_map_latitude', 18.4861);
        $lng = (float) company_setting('default_map_longitude', -69.9312);

        return [
            'lat' => $lat ?: 18.4861,
            'lng' => $lng ?: -69.9312,
            'address' => (string) company_setting('default_map_address', ''),
        ];
    }
}

if (! function_exists('enabled_loan_calculation_methods')) {
    /**
     * Metodos de calculo habilitados para crear prestamos/cotizaciones.
     *
     * @return array<string, string>
     */
    function enabled_loan_calculation_methods(): array
    {
        $all = config('loan_labels.methods', []);
        $enabled = company_setting('enabled_loan_calculation_methods', null);

        if (! is_array($enabled) || $enabled === []) {
            return $all;
        }

        return array_intersect_key($all, array_flip($enabled));
    }
}

if (! function_exists('enabled_payment_allocation_modes')) {
    /**
     * Modos de reparto habilitados para registrar cobros.
     *
     * @return array<string, string>
     */
    function enabled_payment_allocation_modes(): array
    {
        $all = config('loan_labels.payment_allocation_modes', []);
        $enabled = company_setting('enabled_payment_allocation_modes', null);

        if (! is_array($enabled) || $enabled === []) {
            return $all;
        }

        return array_intersect_key($all, array_flip($enabled));
    }
}
