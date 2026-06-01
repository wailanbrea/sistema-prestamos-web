<?php

declare(strict_types=1);

namespace App\Http\Requests\Users;

use App\Support\MenuAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('users.manage');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'phone' => ['nullable', 'string', 'max:50'],
            'password' => ['required', 'confirmed', Password::min(10)->mixedCase()->numbers()],
            'status' => ['required', Rule::in(['active', 'blocked'])],
            'role' => ['required', Rule::exists('roles', 'name')->where('guard_name', 'web')],
            'visible_menus' => ['nullable', 'array'],
            'visible_menus.*' => [Rule::in(MenuAccess::selectableRoutes())],
        ];
    }
}
