<?php

declare(strict_types=1);

namespace App\Http\Requests\Contracts;

use Illuminate\Foundation\Http\FormRequest;

class SignContractRequest extends FormRequest
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
            'signer_name' => ['required', 'string', 'max:180'],
            'signature' => ['required', 'string', 'starts_with:data:image/'],
            'accepted_terms' => ['accepted'],
            'accepted_legal' => ['accepted'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'signature.required' => 'Debes dibujar tu firma antes de continuar.',
            'signature.starts_with' => 'La firma capturada no es válida.',
            'accepted_terms.accepted' => 'Debes aceptar los términos del contrato.',
            'accepted_legal.accepted' => 'Debes reconocer la validez legal de tu firma.',
        ];
    }
}
