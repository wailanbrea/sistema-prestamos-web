<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;
use Database\Seeders\RolePermissionSeeder;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_uses_local_assets_and_is_not_cached(): void
    {
        $response = $this->get(route('login'));

        $response
            ->assertOk()
            ->assertHeader('Pragma', 'no-cache')
            ->assertHeader('Expires', 'Fri, 01 Jan 1990 00:00:00 GMT')
            ->assertDontSee('cdn.tailwindcss.com', false)
            ->assertDontSee('togglePwd(', false)
            ->assertDontSee('eval(', false)
            ->assertSee('build/assets/', false);

        $this->assertStringContainsString(
            'no-store',
            (string) $response->headers->get('Cache-Control')
        );
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_active_user_can_login_and_view_dashboard(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $company = Company::query()->create([
            'name' => 'Empresa Test',
            'status' => 'active',
        ]);

        $user = User::query()->create([
            'company_id' => $company->id,
            'name' => 'Usuario Test',
            'email' => 'usuario@test.local',
            'password' => Hash::make('Password123!'),
            'status' => 'active',
        ]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($company->id);
        $user->assignRole('Administrador');

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'Password123!',
        ])->assertRedirect('/dashboard');

        $this->get('/dashboard')
            ->assertOk()
            ->assertSee('Dashboard financiero')
            ->assertSee('Empresa Test');
    }

    public function test_blocked_user_cannot_login(): void
    {
        $company = Company::query()->create([
            'name' => 'Empresa Test',
            'status' => 'active',
        ]);

        $user = User::query()->create([
            'company_id' => $company->id,
            'name' => 'Usuario Bloqueado',
            'email' => 'bloqueado@test.local',
            'password' => Hash::make('Password123!'),
            'status' => 'blocked',
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'Password123!',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }
}
