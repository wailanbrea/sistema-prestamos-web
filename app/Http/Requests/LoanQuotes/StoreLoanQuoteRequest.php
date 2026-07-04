<?php

declare(strict_types=1);

namespace App\Http\Requests\LoanQuotes;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLoanQuoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('quotes.manage') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'client_id' => ['nullable', 'integer', Rule::exists('clients', 'id')->where('company_id', (int) $this->user()->company_id)],
            'amount' => ['required', 'numeric', 'min:1', 'max:9999999999.99'],
            'interest_rate' => ['required', 'numeric', 'min:0', 'max:999.9999'],
            'interest_type' => ['required', Rule::in(['fixed', 'compound', 'amortized'])],
            'payment_frequency' => ['required', Rule::in(['daily', 'weekly', 'biweekly', 'monthly'])],
            'calculation_method' => ['required', Rule::in(array_keys(enabled_loan_calculation_methods()))],
            'term_quantity' => ['required', 'integer', 'min:1', 'max:1000'],
            'start_date' => ['nullable', 'date'],
            'first_payment_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'client_id' => 'cliente',
            'amount' => 'monto',
            'interest_rate' => 'tasa de interés',
            'interest_type' => 'tipo de interés',
            'payment_frequency' => 'frecuencia de pago',
            'calculation_method' => 'método de cálculo',
            'term_quantity' => 'cantidad de cuotas',
            'start_date' => 'fecha inicial',
            'first_payment_date' => 'fecha de primer pago',
        ];
    }
}
