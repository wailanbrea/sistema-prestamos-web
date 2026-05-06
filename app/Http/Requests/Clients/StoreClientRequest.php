<?php

declare(strict_types=1);

namespace App\Http\Requests\Clients;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('clients.create') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $companyId = (int) $this->user()->company_id;

        return [
            'code' => ['nullable', 'string', 'max:50', Rule::unique('clients', 'code')->where('company_id', $companyId)],
            'full_name' => ['required', 'string', 'max:180'],
            'identification' => ['nullable', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:50'],
            'secondary_phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:150'],
            'address' => ['nullable', 'string', 'max:2000'],
            'workplace' => ['nullable', 'string', 'max:180'],
            'workplace_phone' => ['nullable', 'string', 'max:50'],
            'monthly_income' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'status' => ['required', Rule::in(['active', 'inactive', 'moroso', 'blocked'])],
            'risk_level' => ['required', Rule::in(['low', 'medium', 'high', 'critical'])],
            'notes' => ['nullable', 'string', 'max:3000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'code' => 'código',
            'full_name' => 'nombre completo',
            'identification' => 'identificación',
            'secondary_phone' => 'teléfono secundario',
            'monthly_income' => 'ingreso mensual',
            'risk_level' => 'nivel de riesgo',
        ];
    }
}
