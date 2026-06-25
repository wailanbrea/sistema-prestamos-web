<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Models\Collector;
use App\Models\User;
use App\Support\MenuAccess;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\PermissionRegistrar;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:120'],
        ]);

        /** @var User|null $user */
        $user = User::query()
            ->with('company')
            ->where('email', $validated['email'])
            ->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales no son válidas.'],
            ]);
        }

        if ($user->status !== 'active') {
            return response()->json([
                'message' => 'Tu usuario está inactivo o bloqueado.',
            ], 403);
        }

        if ($user->company?->status !== 'active') {
            return response()->json([
                'message' => 'La empresa asociada está inactiva.',
            ], 403);
        }

        app(PermissionRegistrar::class)->setPermissionsTeamId((int) $user->company_id);

        $user->forceFill(['last_login_at' => now()])->save();

        $token = $user->createToken(
            $validated['device_name'] ?? 'android',
            ['mobile'],
        );

        return response()->json([
            'data' => [
                'token_type' => 'Bearer',
                'access_token' => $token->plainTextToken,
                'user' => $this->userPayload($user->fresh('company.settings') ?? $user),
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Sesión cerrada correctamente.',
        ]);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        Password::sendResetLink([
            'email' => Str::lower($validated['email']),
        ]);

        return response()->json([
            'message' => 'Si el correo existe, enviaremos las instrucciones para restablecer la contraseña.',
        ]);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'token' => ['required', 'string'],
            'password' => ['required', 'confirmed', PasswordRule::min(10)->letters()->mixedCase()->numbers()->symbols()],
        ]);

        $status = Password::reset(
            [
                'email' => Str::lower($validated['email']),
                'token' => $validated['token'],
                'password' => $validated['password'],
                'password_confirmation' => $request->string('password_confirmation')->toString(),
            ],
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                $user->tokens()->delete();

                event(new PasswordReset($user));
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => ['No se pudo restablecer la contraseña con los datos enviados.'],
            ]);
        }

        return response()->json([
            'message' => 'Contraseña restablecida correctamente.',
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user()->load('company.settings');

        return response()->json([
            'data' => $this->userPayload($user),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'status' => $user->status,
            'roles' => $user->getRoleNames()->values(),
            'permissions' => $user->getAllPermissions()->pluck('name')->values(),
            'features' => [
                'accounts_payable' => $user->can('accounts-payable.manage')
                    && MenuAccess::canAccessMenu($user, 'accounts-payable.index'),
            ],
            // Cobrador de campo "real": vinculado a un Collector activo. Distingue al
            // cobrador (usa /collector) del admin que también tiene payments.create.
            'is_collector' => Collector::query()
                ->where('company_id', $user->company_id)
                ->where('user_id', $user->id)
                ->where('status', 'active')
                ->exists(),
            'company' => [
                'id' => $user->company?->id,
                'name' => $user->company?->name,
                'rnc' => $user->company?->rnc,
                'phone' => $user->company?->phone,
                'email' => $user->company?->email,
                'status' => $user->company?->status,
                // Moneda por defecto de la empresa (RD$/US$) para formatear en la app.
                'default_currency' => $user->company?->settings?->default_loan_currency ?: 'RD$',
            ],
        ];
    }
}
