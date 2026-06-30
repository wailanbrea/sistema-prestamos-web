<?php

declare(strict_types=1);

namespace App\Http\Requests\Payments;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('payments.create');
    }

    protected function prepareForValidation(): void
    {
        // Compatibilidad: si no se envía modo, usar reparto automático.
        if (! $this->filled('allocation_mode')) {
            $this->merge(['allocation_mode' => 'auto']);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $companyId = (int) $this->user()->company_id;
        $loanId = $this->input('loan_id');
        $isCustom = $this->input('allocation_mode') === 'custom';

        $installmentExists = Rule::exists('loan_installments', 'id')
            ->where('loan_id', $loanId)
            ->whereNotIn('status', ['paid', 'cancelled']);

        return [
            'loan_id' => [
                'required',
                'integer',
                Rule::exists('loans', 'id')
                    ->where('company_id', $companyId),
            ],
            'collector_id' => [
                'nullable',
                'integer',
                Rule::exists('collectors', 'id')
                    ->where('company_id', $companyId)
                    ->where('status', 'active'),
            ],
            'payment_date' => ['required', 'date'],
            'payment_method' => ['required', Rule::in(array_keys(config('loan_labels.payment_methods')))],
            'allocation_mode' => ['required', Rule::in(['auto', 'principal_and_interest', 'interest_only', 'principal_only', 'current_plus_capital', 'custom'])],
            'amount' => [Rule::requiredIf(! $isCustom), 'nullable', 'numeric', 'min:0.01', 'max:999999999.99'],
            'target_installment_id' => ['nullable', 'integer', $installmentExists],
            'excess_action' => ['nullable', Rule::in(['reject', 'prepayment', 'change'])],
            'capital_prepayment_amount' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'allocations' => [Rule::requiredIf($isCustom), 'array'],
            'allocations.*.installment_id' => ['required_with:allocations', 'integer', $installmentExists],
            'allocations.*.amount' => ['required_with:allocations', 'numeric', 'min:0.01', 'max:999999999.99'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'allocation_mode' => 'modo de reparto',
            'target_installment_id' => 'cuota destino',
            'amount' => 'monto',
            'capital_prepayment_amount' => 'abono a capital',
        ];
    }
}
