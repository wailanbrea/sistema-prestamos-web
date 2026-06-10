<?php

declare(strict_types=1);

namespace App\Http\Requests\Collectors;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCollectorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('collectors.manage');
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'access_mode' => $this->input('access_mode', 'none'),
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
            'access_mode' => ['required', Rule::in(['none', 'existing', 'new'])],
            'user_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where('company_id', $companyId),
                Rule::when($this->input('access_mode') === 'existing', ['required']),
                Rule::unique('collectors', 'user_id'),
            ],
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:50'],
            'user_name' => ['nullable', 'string', 'max:150'],
            'user_email' => [
                'nullable',
                'email',
                'max:150',
                Rule::when($this->input('access_mode') === 'new', ['required']),
                Rule::unique('users', 'email'),
            ],
            'user_password' => [
                'nullable',
                'string',
                'min:8',
                Rule::when($this->input('access_mode') === 'new', ['required']),
            ],
            'commission_type' => ['required', Rule::in(['percentage', 'fixed', 'none'])],
            'commission_base' => ['required', Rule::in(['payment_total', 'principal_only'])],
            'commission_value' => [
                'nullable',
                'numeric',
                'min:0',
                Rule::when($this->input('commission_type') === 'percentage', ['max:100']),
            ],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }
}
