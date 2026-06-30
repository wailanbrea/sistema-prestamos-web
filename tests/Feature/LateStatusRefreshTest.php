<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Company;
use App\Models\Loan;
use App\Models\LoanInstallment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LateStatusRefreshTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_marks_overdue_installments_loans_and_clients_as_late(): void
    {
        $company = Company::query()->create(['name' => 'Empresa Test', 'status' => 'active']);
        $client = $this->clientForCompany((int) $company->id, 'active');
        $loan = $this->loanForClient($client, [
            'late_fee_type' => 'daily_fixed',
            'late_fee_value' => 25,
            'status' => 'active',
        ]);

        LoanInstallment::query()->create([
            'loan_id' => $loan->id,
            'installment_number' => 1,
            'due_date' => now()->subDays(4)->toDateString(),
            'principal_amount' => 1000,
            'interest_amount' => 100,
            'installment_amount' => 1100,
            'status' => 'pending',
        ]);

        $this->artisan('loans:refresh-late-status', ['--company_id' => $company->id])
            ->assertSuccessful();

        $this->assertDatabaseHas('loan_installments', [
            'loan_id' => $loan->id,
            'status' => 'late',
            'days_late' => 4,
            'late_fee' => 100,
        ]);
        $this->assertDatabaseHas('loans', [
            'id' => $loan->id,
            'status' => 'late',
        ]);
        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'status' => 'moroso',
        ]);
    }

    public function test_command_restores_loan_and_client_when_no_installments_are_late(): void
    {
        $company = Company::query()->create(['name' => 'Empresa Test', 'status' => 'active']);
        $client = $this->clientForCompany((int) $company->id, 'moroso');
        $loan = $this->loanForClient($client, ['status' => 'late']);

        LoanInstallment::query()->create([
            'loan_id' => $loan->id,
            'installment_number' => 1,
            'due_date' => now()->addDays(5)->toDateString(),
            'principal_amount' => 1000,
            'interest_amount' => 100,
            'installment_amount' => 1100,
            'status' => 'pending',
        ]);

        $this->artisan('loans:refresh-late-status', ['--company_id' => $company->id])
            ->assertSuccessful();

        $this->assertDatabaseHas('loans', [
            'id' => $loan->id,
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'status' => 'active',
        ]);
    }

    public function test_late_fee_waiver_prevents_regeneration_on_refresh(): void
    {
        $company = Company::query()->create(['name' => 'Empresa Test', 'status' => 'active']);
        $client = $this->clientForCompany((int) $company->id, 'active');
        $loan = $this->loanForClient($client, [
            'late_fee_type' => 'daily_fixed',
            'late_fee_value' => 25,
            'status' => 'late',
        ]);

        LoanInstallment::query()->create([
            'loan_id' => $loan->id,
            'installment_number' => 1,
            'due_date' => now()->subDays(4)->toDateString(),
            'principal_amount' => 1000,
            'interest_amount' => 100,
            'installment_amount' => 1100,
            'late_fee' => 100,
            'late_fee_waived_at' => now(),
            'status' => 'late',
        ]);

        $this->artisan('loans:refresh-late-status', ['--company_id' => $company->id])
            ->assertSuccessful();

        $this->assertDatabaseHas('loan_installments', [
            'loan_id' => $loan->id,
            'days_late' => 4,
            'late_fee' => 0,
            'status' => 'late',
        ]);
    }

    private function clientForCompany(int $companyId, string $status): Client
    {
        return Client::query()->create([
            'company_id' => $companyId,
            'full_name' => 'Cliente Mora',
            'status' => $status,
            'risk_level' => 'low',
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function loanForClient(Client $client, array $overrides = []): Loan
    {
        return Loan::query()->create([
            ...[
                'company_id' => $client->company_id,
                'client_id' => $client->id,
                'loan_number' => 'PRE-LATE-'.fake()->unique()->numerify('####'),
                'principal_amount' => 1000,
                'interest_rate' => 10,
                'interest_type' => 'fixed',
                'payment_frequency' => 'monthly',
                'calculation_method' => 'flat_interest',
                'term_quantity' => 1,
                'installment_amount' => 1100,
                'total_interest' => 100,
                'total_amount' => 1100,
                'remaining_balance' => 1000,
                'late_fee_type' => 'none',
                'late_fee_value' => 0,
                'start_date' => now()->subMonth()->toDateString(),
                'first_payment_date' => now()->subDays(4)->toDateString(),
                'status' => 'active',
            ],
            ...$overrides,
        ]);
    }
}
