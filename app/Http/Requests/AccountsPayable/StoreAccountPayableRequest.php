<?php

declare(strict_types=1);

namespace App\Http\Requests\AccountsPayable;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAccountPayableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('accounts-payable.manage');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $companyId = (int) $this->user()->company_id;

        return [
            'creditor_id' => ['required', 'integer', Rule::exists('creditors', 'id')->where('company_id', $companyId)],
            'currency' => ['required', Rule::in(array_keys(config('loan_labels.currencies')))],
            'principal_amount' => ['required', 'numeric', 'min:0.01', 'max:999999999.99'],
            'interest_rate' => ['required', 'numeric', 'min:0', 'max:999999.9999'],
            'interest_type' => ['required', Rule::in(['fixed', 'compound', 'amortized'])],
            'payment_frequency' => ['required', Rule::in(['daily', 'weekly', 'biweekly', 'monthly'])],
            'calculation_method' => ['required', Rule::in(array_keys(enabled_loan_calculation_methods()))],
            'term_quantity' => ['required', 'integer', 'min:1', 'max:1000'],
            'late_fee_type' => ['required', Rule::in(['none', 'fixed', 'daily_fixed'])],
            'late_fee_value' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
            'disbursement_date' => ['required', 'date'],
            'first_payment_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
