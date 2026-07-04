<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCompanySettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('settings.manage');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:150'],
            'rnc' => ['nullable', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:150'],
            'address' => ['nullable', 'string', 'max:2000'],
            'currency' => ['required', Rule::in(array_keys(config('loan_labels.currencies')))],
            'default_loan_currency' => ['required', Rule::in(array_keys(config('loan_labels.currencies')))],
            'default_account_payable_currency' => ['required', Rule::in(array_keys(config('loan_labels.currencies')))],
            'enabled_loan_calculation_methods' => ['required', 'array', 'min:1'],
            'enabled_loan_calculation_methods.*' => [Rule::in(array_keys(config('loan_labels.methods')))],
            'enabled_payment_allocation_modes' => ['required', 'array', 'min:1'],
            'enabled_payment_allocation_modes.*' => [Rule::in(array_keys(config('loan_labels.payment_allocation_modes')))],
            'default_interest_rate' => ['required', 'numeric', 'min:0', 'max:999.9999'],
            'default_late_fee_type' => ['required', Rule::in(['none', 'fixed', 'daily_percentage', 'daily_fixed'])],
            'default_late_fee_value' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
            'receipt_prefix' => ['required', 'string', 'max:20'],
            'loan_prefix' => ['required', 'string', 'max:20'],
            'quote_prefix' => ['required', 'string', 'max:20'],
            'allow_partial_payments' => ['nullable', 'boolean'],
            'allow_payment_cancellation' => ['nullable', 'boolean'],
            'require_approval_for_loans' => ['nullable', 'boolean'],
            'exclude_sundays_for_daily_loans' => ['nullable', 'boolean'],
            'route_visit_radius_meters' => ['required', 'integer', 'min:20', 'max:500'],
            'default_map_address' => ['nullable', 'string', 'max:2000'],
            'default_map_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'default_map_longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ];

        // Solo quien tiene la habilidad puede cambiar el tipo de licencia (plan);
        // se concede únicamente al dueño del sistema vía Gate::before. Para
        // cualquier otro usuario el campo se ignora (no se valida ni aplica).
        if ($this->user()?->can('companies.manage-plan')) {
            $rules['plan'] = ['required', Rule::in(array_keys(config('plans')))];
        }

        return $rules;
    }
}
