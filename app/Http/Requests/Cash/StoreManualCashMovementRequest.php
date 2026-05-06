<?php

declare(strict_types=1);

namespace App\Http\Requests\Cash;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreManualCashMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('cash.view');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['capital_injection', 'capital_withdrawal', 'adjustment'])],
            'direction' => ['required_if:type,adjustment', 'nullable', Rule::in(['in', 'out'])],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999999.99'],
            'movement_date' => ['required', 'date'],
            'description' => ['required', 'string', 'min:10', 'max:1000'],
        ];
    }
}
