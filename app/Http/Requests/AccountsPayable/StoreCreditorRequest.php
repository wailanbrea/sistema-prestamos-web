<?php

declare(strict_types=1);

namespace App\Http\Requests\AccountsPayable;

use Illuminate\Foundation\Http\FormRequest;

class StoreCreditorRequest extends FormRequest
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
        return [
            'name' => ['required', 'string', 'max:180'],
            'document' => ['nullable', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:150'],
            'address' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'status' => ['required', 'in:active,inactive'],
        ];
    }
}
