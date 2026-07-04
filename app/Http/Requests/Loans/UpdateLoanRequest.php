<?php

declare(strict_types=1);

namespace App\Http\Requests\Loans;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLoanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('loans.update') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $companyId = (int) $this->user()->company_id;
        $loan = $this->route('loan');
        $allowedCalculationMethods = array_keys(enabled_loan_calculation_methods());

        if ($loan?->calculation_method && ! in_array($loan->calculation_method, $allowedCalculationMethods, true)) {
            $allowedCalculationMethods[] = $loan->calculation_method;
        }

        return [
            // Siempre editables.
            'collector_id' => ['nullable', 'integer', Rule::exists('collectors', 'id')->where('company_id', $companyId)],
            'guarantee_description' => ['nullable', 'string', 'max:3000'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'allows_capital_prepayment' => ['nullable', 'boolean'],
            'currency' => ['nullable', Rule::in(array_keys(config('loan_labels.currencies')))],

            // Condiciones financieras (solo se aplican si el préstamo no tiene pagos válidos).
            'principal_amount' => ['nullable', 'numeric', 'min:1', 'max:9999999999.99'],
            'interest_rate' => ['nullable', 'numeric', 'min:0', 'max:999.9999', 'required_with:principal_amount'],
            'interest_type' => ['nullable', Rule::in(['fixed', 'compound', 'amortized']), 'required_with:principal_amount'],
            'payment_frequency' => ['nullable', Rule::in(['daily', 'weekly', 'biweekly', 'monthly']), 'required_with:principal_amount'],
            'calculation_method' => ['nullable', Rule::in($allowedCalculationMethods), 'required_with:principal_amount'],
            'term_quantity' => ['nullable', 'integer', 'min:1', 'max:1000', 'required_with:principal_amount'],
            'late_fee_type' => ['nullable', Rule::in(['none', 'fixed', 'daily_percentage', 'daily_fixed'])],
            'late_fee_value' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'start_date' => ['nullable', 'date', 'required_with:principal_amount'],
            'first_payment_date' => ['nullable', 'date', 'after_or_equal:start_date', 'required_with:principal_amount'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'collector_id' => 'cobrador',
            'currency' => 'moneda',
            'principal_amount' => 'monto principal',
            'interest_rate' => 'tasa de interés',
            'payment_frequency' => 'frecuencia de pago',
            'calculation_method' => 'método de cálculo',
            'term_quantity' => 'cantidad de cuotas',
            'late_fee_type' => 'tipo de mora',
            'start_date' => 'fecha inicial',
            'first_payment_date' => 'fecha de primer pago',
        ];
    }
}
