<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Controllers\Api\V2\Concerns\BuildsApiPayloads;
use App\Models\Client;
use App\Models\Company;
use App\Models\Loan;
use App\Models\LoanInstallment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoanPendingBalanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_loan_payload_balance_includes_principal_interest_and_late_fee(): void
    {
        $company = Company::query()->create([
            'name' => 'Empresa balance',
            'slug' => 'empresa-balance',
            'status' => 'active',
        ]);
        $client = Client::query()->create([
            'company_id' => $company->id,
            'full_name' => 'Sergio Mendez',
            'status' => 'active',
            'risk_level' => 'low',
        ]);
        $loan = Loan::query()->create([
            'company_id' => $company->id,
            'client_id' => $client->id,
            'loan_number' => 'PRE-BALANCE-001',
            'principal_amount' => 1000,
            'interest_rate' => 10,
            'interest_type' => 'fixed',
            'payment_frequency' => 'monthly',
            'calculation_method' => 'flat_interest',
            'term_quantity' => 1,
            'installment_amount' => 1100,
            'total_interest' => 100,
            'total_amount' => 1100,
            'paid_principal' => 700,
            'paid_interest' => 25,
            'remaining_balance' => 300,
            'late_fee_type' => 'fixed',
            'late_fee_value' => 25,
            'start_date' => '2026-05-01',
            'first_payment_date' => '2026-06-01',
            'status' => 'late',
        ]);

        LoanInstallment::query()->create([
            'loan_id' => $loan->id,
            'installment_number' => 1,
            'due_date' => '2026-06-01',
            'principal_amount' => 1000,
            'interest_amount' => 100,
            'installment_amount' => 1100,
            'paid_principal' => 700,
            'paid_interest' => 25,
            'late_fee' => 25,
            'paid_late_fee' => 0,
            'total_paid' => 725,
            'status' => 'partial',
        ]);

        $loan = Loan::query()->withDueSummary()->with('client')->findOrFail($loan->id);
        $builder = new class
        {
            use BuildsApiPayloads;

            /** @return array<string, mixed> */
            public function build(Loan $loan): array
            {
                return $this->loanPayload($loan);
            }
        };

        $payload = $builder->build($loan);

        $this->assertSame(300.0, $payload['remaining_principal']);
        $this->assertSame(300.0, $payload['pending_principal']);
        $this->assertSame(75.0, $payload['pending_interest']);
        $this->assertSame(25.0, $payload['pending_late_fee']);
        $this->assertSame(400.0, $payload['total_pending_balance']);
        $this->assertSame(400.0, $payload['remaining_balance']);
        $this->assertSame(300.0, (float) $loan->getRawOriginal('remaining_balance'));
    }
}
