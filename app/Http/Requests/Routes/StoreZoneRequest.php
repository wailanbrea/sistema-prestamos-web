<?php

declare(strict_types=1);

namespace App\Http\Requests\Routes;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreZoneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('routes.manage');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('zones', 'name')->where('company_id', (int) $this->user()->company_id),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
