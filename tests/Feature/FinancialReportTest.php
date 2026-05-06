<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Collector;
use App\Models\Company;
use App\Models\Expense;
use App\Models\Loan;
use App\Models\LoanInstallment;
use App\Models\Payment;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class FinancialReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_financial_report_for_own_company(): void
    {
        $user = $this->adminUser();
        $this->seedFinancialData((int) $user->company_id);

        $otherCompany = Company::query()->create(['name' => 'Otra Empresa', 'status' => 'active']);
        $this->seedFinancialData((int) $otherCompany->id, 'Ajeno');

        $this->actingAs($user)
            ->get('/reportes?date_from=2026-05-01&date_to=2026-05-31')
            ->assertOk()
            ->assertSee('RD$ 1,100.00')
            ->assertSee('RD$ 50.00')
            ->assertSee('Cliente Reporte')
            ->assertDontSee('Ajeno');
    }

    public function test_admin_can_export_financial_report_as_csv(): void
    {
        $user = $this->adminUser();
        $this->seedFinancialData((int) $user->company_id);

        $response = $this->actingAs($user)
            ->get('/reportes/financiero.csv?date_from=2026-05-01&date_to=2026-05-31')
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();

        $this->assertStringContainsString('total_payments', $content);
        $this->assertStringContainsString('Cliente Reporte', $content);
    }

    public function test_admin_can_export_financial_report_as_pdf(): void
    {
        $user = $this->adminUser();
        $this->seedFinancialData((int) $user->company_id);

        $this->actingAs($user)
            ->get('/reportes/financiero.pdf?date_from=2026-05-01&date_to=2026-05-31')
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    private function adminUser(): User
    {
        $this->seed(RolePermissionSeeder::class);

        $company = Company::query()->create([
            'name' => 'Empresa Test',
            'status' => 'active',
        ]);

        $user = User::query()->create([
            'company_id' => $company->id,
            'name' => 'Admin Test',
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('Password123!'),
            'status' => 'active',
        ]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($company->id);
        $user->assignRole('Administrador');

        return $user;
    }

    private function seedFinancialData(int $companyId, string $suffix = 'Reporte'): void
    {
        $client = Client::query()->create([
            'company_id' => $companyId,
            'full_name' => "Cliente {$suffix}",
            'phone' => '809-555-1111',
            'status' => 'active',
            'risk_level' => 'low',
        ]);
        $collector = Collector::query()->create([
            'company_id' => $companyId,
            'name' => "Cobrador {$suffix}",
            'commission_type' => 'none',
            'commission_value' => 0,
            'status' => 'active',
        ]);
        $loan = Loan::query()->create([
            'company_id' => $companyId,
            'client_id' => $client->id,
            'collector_id' => $collector->id,
            'loan_number' => 'PRE-REP-'.fake()->unique()->numerify('####'),
            'principal_amount' => 2000,
            'interest_rate' => 10,
            'interest_type' => 'fixed',
            'payment_frequency' => 'monthly',
            'calculation_method' => 'flat_interest',
            'term_quantity' => 2,
            'installment_amount' => 1100,
            'total_interest' => 200,
            'total_amount' => 2200,
            'paid_principal' => 1000,
            'paid_interest' => 100,
            'remaining_balance' => 1000,
            'late_fee_type' => 'none',
            'late_fee_value' => 0,
            'start_date' => '2026-05-01',
            'first_payment_date' => '2026-05-15',
            'status' => 'active',
        ]);
        LoanInstallment::query()->create([
            'loan_id' => $loan->id,
            'installment_number' => 2,
            'due_date' => '2026-05-01',
            'principal_amount' => 1000,
            'interest_amount' => 100,
            'installment_amount' => 1100,
            'status' => 'late',
        ]);
        Payment::query()->create([
            'company_id' => $companyId,
            'loan_id' => $loan->id,
            'client_id' => $client->id,
            'collector_id' => $collector->id,
            'receipt_number' => 'REC-REP-'.fake()->unique()->numerify('####'),
            'payment_date' => '2026-05-06',
            'amount' => 1100,
            'principal_paid' => 1000,
            'interest_paid' => 100,
            'late_fee_paid' => 0,
            'payment_method' => 'cash',
            'previous_balance' => 2000,
            'new_balance' => 1000,
        ]);
        Expense::query()->create([
            'company_id' => $companyId,
            'description' => "Gasto {$suffix}",
            'amount' => 50,
            'expense_date' => '2026-05-06',
            'payment_method' => 'cash',
        ]);
    }
}
