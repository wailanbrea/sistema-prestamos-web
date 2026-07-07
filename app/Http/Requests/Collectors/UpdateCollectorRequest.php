<?php

declare(strict_types=1);

namespace App\Http\Requests\Collectors;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCollectorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('collectors.manage');
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'commission_base' => $this->input('commission_base', 'payment_total'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $companyId = (int) $this->user()->company_id;

        return [
            'user_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where('company_id', $companyId),
                Rule::unique('collectors', 'user_id')->ignore($this->route('collector')),
            ],
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:50'],
            'commission_type' => ['required', Rule::in(['percentage', 'fixed', 'none'])],
            'commission_base' => ['required', Rule::in(['payment_total', 'principal_only'])],
            'commission_value' => [
                'nullable',
                'numeric',
                'min:0',
                Rule::when($this->input('commission_type') === 'percentage', ['max:100']),
            ],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'loan_ids' => ['nullable', 'array'],
            'loan_ids.*' => [
                'integer',
                Rule::exists('loans', 'id')
                    ->where('company_id', $companyId)
                    ->whereIn('status', ['active', 'late']),
            ],
        ];
    }
}
