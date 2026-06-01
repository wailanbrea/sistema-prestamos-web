<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Company;
use App\Models\Loan;
use App\Models\User;
use App\Notifications\LoanCreatedNotification;
use App\Services\Notifications\EventNotifier;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class NotificationManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_event_notifier_notifies_other_active_users_except_actor(): void
    {
        $company = Company::query()->create(['name' => 'Empresa', 'status' => 'active']);
        $actor = $this->user($company, 'actor@example.com');
        $other = $this->user($company, 'other@example.com');
        $inactive = $this->user($company, 'inactive@example.com', 'inactive');

        app(EventNotifier::class)->loanCreated($this->fakeLoan($company), $actor->id);

        $this->assertSame(0, $actor->unreadNotifications()->count());
        $this->assertSame(1, $other->unreadNotifications()->count());
        $this->assertSame(0, $inactive->unreadNotifications()->count());

        $payload = $other->unreadNotifications()->first()->data;
        $this->assertSame('loan_created', $payload['type']);
        $this->assertStringContainsString('PRE-TEST-1', $payload['message']);
    }

    public function test_user_can_view_and_mark_notifications_read(): void
    {
        $company = Company::query()->create(['name' => 'Empresa', 'status' => 'active']);
        $user = $this->user($company, 'u@example.com');

        $user->notify(new LoanCreatedNotification(1, 'PRE-TEST-1', 'Juan', 'RD$ 1,000.00'));
        $this->assertSame(1, $user->unreadNotifications()->count());

        $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Nuevo préstamo');

        $id = $user->notifications()->first()->id;
        $this->actingAs($user)
            ->post(route('notifications.read', $id))
            ->assertRedirect();
        $this->assertSame(0, $user->fresh()->unreadNotifications()->count());

        $user->notify(new LoanCreatedNotification(2, 'PRE-TEST-2', 'Ana', 'RD$ 2,000.00'));
        $this->actingAs($user)
            ->post(route('notifications.read-all'))
            ->assertRedirect();
        $this->assertSame(0, $user->fresh()->unreadNotifications()->count());
    }

    public function test_user_cannot_mark_another_users_notification(): void
    {
        $company = Company::query()->create(['name' => 'Empresa', 'status' => 'active']);
        $owner = $this->user($company, 'owner-notif@example.com');
        $intruder = $this->user($company, 'intruder@example.com');

        $owner->notify(new LoanCreatedNotification(1, 'PRE-TEST-1', 'Juan', 'RD$ 1,000.00'));
        $id = $owner->notifications()->first()->id;

        $this->actingAs($intruder)
            ->post(route('notifications.read', $id))
            ->assertNotFound();

        $this->assertSame(1, $owner->unreadNotifications()->count());
    }

    private function user(Company $company, string $email, string $status = 'active'): User
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::query()->create([
            'company_id' => $company->id,
            'name' => 'Usuario',
            'email' => $email,
            'password' => Hash::make('Password123!'),
            'status' => $status,
        ]);

        app(PermissionRegistrar::class)->setPermissionsTeamId((int) $company->id);
        $user->assignRole('Administrador');

        return $user;
    }

    private function fakeLoan(Company $company): Loan
    {
        $client = new Client();
        $client->full_name = 'María Rodríguez';

        $loan = new Loan();
        $loan->id = 1;
        $loan->company_id = $company->id;
        $loan->loan_number = 'PRE-TEST-1';
        $loan->principal_amount = 120000;
        $loan->setRelation('client', $client);

        return $loan;
    }
}
