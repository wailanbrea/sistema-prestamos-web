<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Company;
use App\Models\Scopes\CompanyScope;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Blinda el aislamiento multi-empresa garantizado por el global scope CompanyScope.
 * Ningún usuario autenticado debe poder leer datos de otra empresa, ni siquiera
 * conociendo el ID exacto del registro.
 */
class CompanyScopeIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_only_sees_own_company_records(): void
    {
        [$companyA, $userA] = $this->companyWithUser('Empresa A', 'a@example.com');
        [$companyB] = $this->companyWithUser('Empresa B', 'b@example.com');

        $clientA = Client::query()->create(['company_id' => $companyA->id, 'full_name' => 'Cliente A']);
        Client::query()->create(['company_id' => $companyB->id, 'full_name' => 'Cliente B']);

        $this->actingAs($userA);

        $visible = Client::query()->get();

        $this->assertCount(1, $visible, 'El usuario solo debe ver los clientes de su empresa.');
        $this->assertTrue($visible->contains('id', $clientA->id));
    }

    public function test_authenticated_user_cannot_fetch_other_company_record_by_id(): void
    {
        [, $userA] = $this->companyWithUser('Empresa A', 'a@example.com');
        [$companyB] = $this->companyWithUser('Empresa B', 'b@example.com');

        $clientB = Client::query()->create(['company_id' => $companyB->id, 'full_name' => 'Cliente B']);

        $this->actingAs($userA);

        // Aunque conozca el ID exacto de la otra empresa, no debe poder resolverlo.
        $this->assertNull(Client::query()->find($clientB->id));
    }

    public function test_scope_can_be_bypassed_explicitly_for_legitimate_cross_company_jobs(): void
    {
        [$companyA, $userA] = $this->companyWithUser('Empresa A', 'a@example.com');
        [$companyB] = $this->companyWithUser('Empresa B', 'b@example.com');

        Client::query()->create(['company_id' => $companyA->id, 'full_name' => 'Cliente A']);
        Client::query()->create(['company_id' => $companyB->id, 'full_name' => 'Cliente B']);

        $this->actingAs($userA);

        // Un proceso legítimo cross-empresa (p. ej. un comando agendado) puede saltar el scope.
        $all = Client::query()->withoutGlobalScope(CompanyScope::class)->get();

        $this->assertCount(2, $all);
    }

    public function test_scope_is_inactive_without_authenticated_user(): void
    {
        [$companyA] = $this->companyWithUser('Empresa A', 'a@example.com');
        [$companyB] = $this->companyWithUser('Empresa B', 'b@example.com');

        Client::query()->create(['company_id' => $companyA->id, 'full_name' => 'Cliente A']);
        Client::query()->create(['company_id' => $companyB->id, 'full_name' => 'Cliente B']);

        // Sin usuario autenticado (consola, seeders, colas) el scope no filtra.
        $this->assertCount(2, Client::query()->get());
    }

    /**
     * @return array{0: Company, 1: User}
     */
    private function companyWithUser(string $companyName, string $email): array
    {
        $company = Company::query()->create([
            'name' => $companyName,
            'status' => 'active',
        ]);

        $user = User::query()->create([
            'company_id' => $company->id,
            'name' => $companyName.' Admin',
            'email' => $email,
            'password' => Hash::make('Password123!'),
            'status' => 'active',
        ]);

        return [$company, $user];
    }
}
