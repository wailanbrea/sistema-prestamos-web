<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Company;
use App\Models\Document;
use App\Models\Loan;
use App\Models\LoanInstallment;
use App\Models\Payment;
use App\Models\PaymentDetail;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class DocumentGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_generate_promissory_note(): void
    {
        Storage::fake('local');
        $user = $this->adminUser();
        $loan = $this->loanForCompany((int) $user->company_id);

        $this->actingAs($user)
            ->post('/documentos/prestamo', [
                'loan_id' => $loan->id,
                'document_type' => 'promissory_note',
            ])
            ->assertRedirect();

        $document = Document::query()->firstOrFail();

        $this->assertSame('promissory_note', $document->document_type);
        $this->assertSame($loan->id, $document->loan_id);
        Storage::disk('local')->assertExists($document->file_path);
    }

    public function test_balance_letter_requires_paid_loan(): void
    {
        Storage::fake('local');
        $user = $this->adminUser();
        $loan = $this->loanForCompany((int) $user->company_id);

        $this->actingAs($user)
            ->from('/documentos')
            ->post('/documentos/prestamo', [
                'loan_id' => $loan->id,
                'document_type' => 'balance_letter',
            ])
            ->assertRedirect('/documentos')
            ->assertSessionHasErrors('document_type');

        $this->assertDatabaseCount('documents', 0);
    }

    public function test_admin_can_generate_payment_receipt_and_download_document(): void
    {
        Storage::fake('local');
        $user = $this->adminUser();
        $loan = $this->loanForCompany((int) $user->company_id);
        $payment = $this->paymentForLoan($loan);

        $this->actingAs($user)
            ->post('/documentos/recibo-pago', [
                'payment_id' => $payment->id,
            ])
            ->assertRedirect();

        $document = Document::query()->where('document_type', 'payment_receipt')->firstOrFail();
        Storage::disk('local')->assertExists($document->file_path);

        $this->actingAs($user)
            ->get(route('documents.download', $document))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_user_cannot_download_document_from_another_company(): void
    {
        Storage::fake('local');
        $user = $this->adminUser();
        $otherCompany = Company::query()->create(['name' => 'Otra Empresa', 'status' => 'active']);
        Storage::disk('local')->put('documents/foreign.pdf', 'PDF');
        $foreignDocument = Document::query()->create([
            'company_id' => $otherCompany->id,
            'document_type' => 'promissory_note',
            'title' => 'Documento ajeno',
            'file_path' => 'documents/foreign.pdf',
        ]);

        $this->actingAs($user)
            ->get(route('documents.download', $foreignDocument))
            ->assertNotFound();
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

    private function loanForCompany(int $companyId): Loan
    {
        $client = Client::query()->create([
            'company_id' => $companyId,
            'full_name' => 'Cliente Documento',
            'identification' => '001-0000000-1',
            'address' => 'Santo Domingo',
            'status' => 'active',
            'risk_level' => 'low',
        ]);

        return Loan::query()->create([
            'company_id' => $companyId,
            'client_id' => $client->id,
            'loan_number' => 'PRE-DOC-'.fake()->unique()->numerify('####'),
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
            'start_date' => '2026-05-01',
            'first_payment_date' => '2026-06-01',
            'status' => 'active',
        ]);
    }

    private function paymentForLoan(Loan $loan): Payment
    {
        $installment = LoanInstallment::query()->create([
            'loan_id' => $loan->id,
            'installment_number' => 1,
            'due_date' => '2026-06-01',
            'principal_amount' => 1000,
            'interest_amount' => 100,
            'installment_amount' => 1100,
            'paid_principal' => 1000,
            'paid_interest' => 100,
            'total_paid' => 1100,
            'status' => 'paid',
        ]);

        $payment = Payment::query()->create([
            'company_id' => $loan->company_id,
            'loan_id' => $loan->id,
            'client_id' => $loan->client_id,
            'receipt_number' => 'REC-DOC-'.fake()->unique()->numerify('####'),
            'payment_date' => '2026-05-06',
            'amount' => 1100,
            'principal_paid' => 1000,
            'interest_paid' => 100,
            'payment_method' => 'cash',
            'previous_balance' => 1000,
            'new_balance' => 0,
        ]);

        PaymentDetail::query()->create([
            'payment_id' => $payment->id,
            'installment_id' => $installment->id,
            'principal_paid' => 1000,
            'interest_paid' => 100,
            'amount_paid' => 1100,
        ]);

        return $payment;
    }
}
