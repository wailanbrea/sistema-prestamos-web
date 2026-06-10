<?php

declare(strict_types=1);

namespace App\Http\Requests\Clients;

use Illuminate\Foundation\Http\FormRequest;

class SubmitClientRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'code' => ['nullable', 'string', 'max:50'],
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
            'monthly_income' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'id_front' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'id_back' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }
}
