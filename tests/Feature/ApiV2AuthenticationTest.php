<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Laravel\Sanctum\PersonalAccessToken;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ApiV2AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_user_can_login_and_receive_mobile_token(): void
    {
        $user = $this->userWithRole('Administrador');

        $this->postJson('/api/v2/auth/login', [
            'email' => $user->email,
            'password' => 'Password123!',
            'device_name' => 'Pixel Test',
        ])
            ->assertOk()
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.user.email', $user->email)
            ->assertJsonPath('data.user.company.id', $user->company_id)
            ->assertJsonStructure([
                'data' => [
                    'access_token',
                    'user' => ['roles', 'permissions'],
                ],
            ]);

        $this->assertDatabaseCount('personal_access_tokens', 1);
        $this->assertNotNull($user->fresh()->last_login_at);
    }

    public function test_login_rejects_invalid_credentials(): void
    {
        $user = $this->userWithRole('Administrador');

        $this->postJson('/api/v2/auth/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_blocked_user_cannot_login_to_mobile_api(): void
    {
        $user = $this->userWithRole('Administrador', status: 'blocked');

        $this->postJson('/api/v2/auth/login', [
            'email' => $user->email,
            'password' => 'Password123!',
        ])
            ->assertForbidden()
            ->assertJsonPath('message', 'Tu usuario está inactivo o bloqueado.');
    }

    public function test_authenticated_user_can_read_profile_and_logout(): void
    {
        $user = $this->userWithRole('Administrador');

        $login = $this->postJson('/api/v2/auth/login', [
            'email' => $user->email,
            'password' => 'Password123!',
        ])->assertOk();

        $token = $login->json('data.access_token');

        $this->withToken($token)
            ->getJson('/api/v2/me')
            ->assertOk()
            ->assertJsonPath('data.email', $user->email);

        $this->withToken($token)
            ->postJson('/api/v2/auth/logout')
            ->assertOk();

        $this->assertSame(0, PersonalAccessToken::query()->count());
    }

    public function test_mobile_user_can_request_password_reset_link(): void
    {
        Notification::fake();
        $user = $this->userWithRole('Administrador');

        $this->postJson('/api/v2/auth/forgot-password', [
            'email' => $user->email,
        ])
            ->assertOk()
            ->assertJsonPath('message', 'Si el correo existe, enviaremos las instrucciones para restablecer la contraseña.');

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_mobile_user_can_reset_password_with_valid_token(): void
    {
        $user = $this->userWithRole('Administrador');
        $token = Password::createToken($user);

        $this->postJson('/api/v2/auth/reset-password', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'NuevaClave123!',
            'password_confirmation' => 'NuevaClave123!',
        ])
            ->assertOk()
            ->assertJsonPath('message', 'Contraseña restablecida correctamente.');

        $this->assertTrue(Hash::check('NuevaClave123!', $user->fresh()->password));
    }

    private function userWithRole(string $role, string $status = 'active'): User
    {
        $this->seed(RolePermissionSeeder::class);

        $company = Company::query()->create([
            'name' => 'Empresa API',
            'status' => 'active',
        ]);

        $user = User::query()->create([
            'company_id' => $company->id,
            'name' => 'Usuario API',
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('Password123!'),
            'status' => $status,
        ]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($company->id);
        $user->assignRole($role);

        return $user;
    }
}
