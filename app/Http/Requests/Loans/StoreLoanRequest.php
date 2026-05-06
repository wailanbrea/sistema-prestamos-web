<?php

declare(strict_types=1);

namespace App\Http\Requests\Loans;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLoanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('loans.create') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $companyId = (int) $this->user()->company_id;

        return [
            'quote_id' => ['nullable', 'integer', Rule::exists('loan_quotes', 'id')->where('company_id', $companyId)],
            'client_id' => ['required', 'integer', Rule::exists('clients', 'id')->where('company_id', $companyId)],
            'collector_id' => ['nullable', 'integer', Rule::exists('collectors', 'id')->where('company_id', $companyId)],
            'principal_amount' => ['required_without:quote_id', 'nullable', 'numeric', 'min:1', 'max:9999999999.99'],
            'interest_rate' => ['required_without:quote_id', 'nullable', 'numeric', 'min:0', 'max:999.9999'],
            'interest_type' => ['required_without:quote_id', 'nullable', Rule::in(['fixed', 'compound', 'amortized'])],
            'payment_frequency' => ['required_without:quote_id', 'nullable', Rule::in(['daily', 'weekly', 'biweekly', 'monthly'])],
            'calculation_method' => ['required_without:quote_id', 'nullable', Rule::in(['flat_interest', 'fixed_installment', 'capital_plus_interest', 'interest_only', 'french_amortization'])],
            'term_quantity' => ['required_without:quote_id', 'nullable', 'integer', 'min:1', 'max:1000'],
            'late_fee_type' => ['required', Rule::in(['none', 'fixed', 'daily_percentage', 'daily_fixed'])],
            'late_fee_value' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'start_date' => ['required', 'date'],
            'first_payment_date' => ['required', 'date', 'after_or_equal:start_date'],
            'guarantee_description' => ['nullable', 'string', 'max:3000'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'quote_id' => 'cotización',
            'client_id' => 'cliente',
            'collector_id' => 'cobrador',
            'principal_amount' => 'monto principal',
            'interest_rate' => 'tasa de interés',
            'payment_frequency' => 'frecuencia de pago',
            'calculation_method' => 'método de cálculo',
            'term_quantity' => 'cantidad de cuotas',
            'late_fee_type' => 'tipo de mora',
            'late_fee_value' => 'valor de mora',
            'start_date' => 'fecha inicial',
            'first_payment_date' => 'fecha de primer pago',
        ];
    }
}
