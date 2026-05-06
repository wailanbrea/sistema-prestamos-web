<?php

declare(strict_types=1);

namespace App\Http\Requests\Routes;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRouteRequest extends FormRequest
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
        $companyId = (int) $this->user()->company_id;
        $routeId = (int) $this->route('route');

        return [
            'zone_id' => ['nullable', 'integer', Rule::exists('zones', 'id')->where('company_id', $companyId)],
            'collector_id' => ['nullable', 'integer', Rule::exists('collectors', 'id')->where('company_id', $companyId)],
            'name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('routes', 'name')->where('company_id', $companyId)->ignore($routeId),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'client_ids' => ['nullable', 'array'],
            'client_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('clients', 'id')->where('company_id', $companyId),
            ],
        ];
    }
}
