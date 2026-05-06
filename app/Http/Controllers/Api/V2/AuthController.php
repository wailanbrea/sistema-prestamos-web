<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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
                'user' => $this->userPayload($user->fresh('company') ?? $user),
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

    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user()->load('company');

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
            'company' => [
                'id' => $user->company?->id,
                'name' => $user->company?->name,
                'rnc' => $user->company?->rnc,
                'phone' => $user->company?->phone,
                'email' => $user->company?->email,
                'status' => $user->company?->status,
            ],
        ];
    }
}
