<?php

declare(strict_types=1);

namespace App\Services\Settings;

use App\Models\Company;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;

class CompanySettingsService
{
    public function __construct(private readonly AuditService $auditService) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Company $company, array $data, int $userId): Company
    {
        return DB::transaction(function () use ($company, $data, $userId): Company {
            $companyData = [
                'name' => $data['name'],
                'rnc' => $data['rnc'] ?? null,
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'] ?? null,
                'address' => $data['address'] ?? null,
            ];

            // El plan solo llega cuando el editor es el dueño del sistema
            // (ver UpdateCompanySettingsRequest); de lo contrario se preserva.
            if (array_key_exists('plan', $data)) {
                $companyData['plan'] = $data['plan'];
            }
            $settingsData = [
                'currency' => $data['currency'],
                'default_loan_currency' => $data['default_loan_currency'],
                'default_account_payable_currency' => $data['default_account_payable_currency'],
                'enabled_loan_calculation_methods' => array_values(array_unique($data['enabled_loan_calculation_methods'] ?? [])),
                'enabled_payment_allocation_modes' => array_values(array_unique($data['enabled_payment_allocation_modes'] ?? [])),
                'default_interest_rate' => $data['default_interest_rate'],
                'default_late_fee_type' => $data['default_late_fee_type'],
                'default_late_fee_value' => $data['default_late_fee_value'],
                'receipt_prefix' => $data['receipt_prefix'],
                'loan_prefix' => $data['loan_prefix'],
                'quote_prefix' => $data['quote_prefix'],
                'allow_partial_payments' => (bool) ($data['allow_partial_payments'] ?? false),
                'allow_payment_cancellation' => (bool) ($data['allow_payment_cancellation'] ?? false),
                'require_approval_for_loans' => (bool) ($data['require_approval_for_loans'] ?? false),
                'exclude_sundays_for_daily_loans' => (bool) ($data['exclude_sundays_for_daily_loans'] ?? false),
                'route_visit_radius_meters' => (int) $data['route_visit_radius_meters'],
                'default_map_address' => $data['default_map_address'] ?? null,
                'default_map_latitude' => $data['default_map_latitude'] ?? null,
                'default_map_longitude' => $data['default_map_longitude'] ?? null,
            ];

            $oldValues = [
                'company' => $company->only(array_keys($companyData)),
                'settings' => $company->settings?->only(array_keys($settingsData)),
            ];

            $company->update($companyData);
            $company->settings()->updateOrCreate(['company_id' => $company->id], $settingsData);

            $company->refresh()->load('settings');

            $this->auditService->record(
                action: 'settings_updated',
                module: 'settings',
                companyId: (int) $company->id,
                userId: $userId,
                auditable: $company,
                description: 'Configuración de empresa actualizada.',
                oldValues: $oldValues,
                newValues: [
                    'company' => $company->only(array_keys($companyData)),
                    'settings' => $company->settings?->only(array_keys($settingsData)),
                ],
            );

            return $company;
        });
    }
}
