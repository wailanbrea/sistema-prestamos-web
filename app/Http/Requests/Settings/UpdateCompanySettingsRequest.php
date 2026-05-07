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
        return [
            'name' => ['required', 'string', 'max:150'],
            'rnc' => ['nullable', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:150'],
            'address' => ['nullable', 'string', 'max:2000'],
            'currency' => ['required', 'string', 'max:10'],
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
        ];
    }
}
