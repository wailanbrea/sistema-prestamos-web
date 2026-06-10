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
            'address' => ['required', 'string', 'max:2000'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'location_reference' => ['nullable', 'string', 'max:180'],
            'workplace' => ['nullable', 'string', 'max:180'],
            'workplace_phone' => ['nullable', 'string', 'max:50'],
            'workplace_address' => ['nullable', 'string', 'max:2000'],
            'workplace_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'workplace_longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'workplace_location_reference' => ['nullable', 'string', 'max:180'],
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
            'code' => 'codigo',
            'full_name' => 'nombre completo',
            'identification' => 'identificacion',
            'secondary_phone' => 'telefono secundario',
            'address' => 'direccion',
            'latitude' => 'latitud',
            'longitude' => 'longitud',
            'location_reference' => 'referencia de ubicacion',
            'workplace_address' => 'direccion laboral',
            'workplace_latitude' => 'latitud laboral',
            'workplace_longitude' => 'longitud laboral',
            'workplace_location_reference' => 'referencia laboral',
            'monthly_income' => 'ingreso mensual',
            'risk_level' => 'nivel de riesgo',
        ];
    }
}
