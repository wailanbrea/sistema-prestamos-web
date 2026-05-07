<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Collector;
use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\ExpenseCategory;
use App\Models\Route as LendingRoute;
use App\Models\User;
use App\Models\Zone;
use App\Services\Loans\LateStatusRefreshService;
use App\Services\Loans\LoanService;
use App\Services\Payments\PaymentService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;

class DemoLoanPortfolioSeeder extends Seeder
{
    private const ADMIN_EMAIL = 'admin@sistemaprestamista.local';
    private const COLLECTOR_EMAIL = 'cobrador@sistemaprestamista.local';
    private const MARKER = 'DEMO_PORTFOLIO_V1';

    public function run(): void
    {
        /** @var LoanService $loanService */
        $loanService = app(LoanService::class);
        /** @var PaymentService $paymentService */
        $paymentService = app(PaymentService::class);
        /** @var LateStatusRefreshService $lateStatusRefreshService */
        $lateStatusRefreshService = app(LateStatusRefreshService::class);

        $company = $this->company();
        $admin = $this->admin($company);
        $collector = $this->collector($company);
        $this->supportingCatalogs($company);
        $clients = $this->clients($company);
        $this->route($company, $collector, $clients);

        if ($company->loans()->where('notes', 'like', '%'.self::MARKER.'%')->exists()) {
            return;
        }

        $monthlyLoan = $loanService->create(
            companyId: (int) $company->id,
            userId: (int) $admin->id,
            data: [
                'client_id' => $clients['monthly']->id,
                'collector_id' => $collector->id,
                'principal_amount' => 120000,
                'interest_rate' => 12,
                'interest_type' => 'fixed',
                'payment_frequency' => 'monthly',
                'calculation_method' => 'flat_interest',
                'term_quantity' => 6,
                'late_fee_type' => 'daily_fixed',
                'late_fee_value' => 150,
                'start_date' => '2026-05-06',
                'first_payment_date' => '2026-06-06',
                'guarantee_description' => 'Motor Honda Lead 2023 y pagaré notarial.',
                'notes' => self::MARKER.' Mensual plano: capital RD$120,000, interés 12%, 6 cuotas de RD$22,400.',
            ],
        );

        $weeklyLoan = $loanService->create(
            companyId: (int) $company->id,
            userId: (int) $admin->id,
            data: [
                'client_id' => $clients['weekly']->id,
                'collector_id' => $collector->id,
                'principal_amount' => 25000,
                'interest_rate' => 5,
                'interest_type' => 'fixed',
                'payment_frequency' => 'weekly',
                'calculation_method' => 'capital_plus_interest',
                'term_quantity' => 8,
                'late_fee_type' => 'fixed',
                'late_fee_value' => 300,
                'start_date' => '2026-04-29',
                'first_payment_date' => '2026-05-06',
                'guarantee_description' => 'Garantía solidaria con dos referencias.',
                'notes' => self::MARKER.' Semanal capital+interés: RD$25,000, 5%, 8 cuotas de RD$4,375.',
            ],
        );

        $dailyLoan = $loanService->create(
            companyId: (int) $company->id,
            userId: (int) $admin->id,
            data: [
                'client_id' => $clients['dailyLate']->id,
                'collector_id' => $collector->id,
                'principal_amount' => 15000,
                'interest_rate' => 10,
                'interest_type' => 'fixed',
                'payment_frequency' => 'daily',
                'calculation_method' => 'flat_interest',
                'term_quantity' => 10,
                'late_fee_type' => 'daily_fixed',
                'late_fee_value' => 75,
                'start_date' => '2026-04-20',
                'first_payment_date' => '2026-04-21',
                'guarantee_description' => 'Nevera comercial y contrato firmado.',
                'notes' => self::MARKER.' Diario atrasado: RD$15,000, 10%, 10 cuotas de RD$1,650, mora diaria RD$75.',
            ],
        );

        $paymentService->register([
            'loan_id' => $weeklyLoan->id,
            'collector_id' => $collector->id,
            'payment_date' => '2026-05-06',
            'amount' => 4375,
            'payment_method' => 'cash',
            'created_by' => $collector->user_id,
        ]);

        $lateStatusRefreshService->refresh((int) $company->id, CarbonImmutable::parse('2026-05-06'));

        $monthlyLoan->refresh();
        $dailyLoan->refresh();
    }

    private function company(): Company
    {
        $company = Company::query()->firstOrCreate(
            ['email' => self::ADMIN_EMAIL],
            [
                'name' => 'Prestamista Demo RD',
                'rnc' => '131000001',
                'phone' => '809-555-1000',
                'address' => 'Av. Winston Churchill, Santo Domingo',
                'status' => 'active',
            ],
        );

        $company->forceFill([
            'name' => 'Prestamista Demo RD',
            'rnc' => '131000001',
            'phone' => '809-555-1000',
            'address' => 'Av. Winston Churchill, Santo Domingo',
            'status' => 'active',
        ])->save();

        CompanySetting::query()->updateOrCreate(
            ['company_id' => $company->id],
            [
                'currency' => 'RD$',
                'default_interest_rate' => 10,
                'default_late_fee_type' => 'daily_fixed',
                'default_late_fee_value' => 75,
                'receipt_prefix' => 'REC',
                'loan_prefix' => 'PRE',
                'quote_prefix' => 'COT',
                'allow_partial_payments' => true,
                'allow_payment_cancellation' => true,
                'require_approval_for_loans' => false,
                'exclude_sundays_for_daily_loans' => true,
            ],
        );

        return $company;
    }

    private function admin(Company $company): User
    {
        $user = User::query()->updateOrCreate(
            ['email' => self::ADMIN_EMAIL],
            [
                'company_id' => $company->id,
                'name' => 'Administrador Demo',
                'phone' => '809-555-1001',
                'password' => Hash::make('Password123!'),
                'status' => 'active',
            ],
        );

        app(PermissionRegistrar::class)->setPermissionsTeamId($company->id);
        $user->assignRole('Administrador');

        return $user;
    }

    private function collector(Company $company): Collector
    {
        $user = User::query()->updateOrCreate(
            ['email' => self::COLLECTOR_EMAIL],
            [
                'company_id' => $company->id,
                'name' => 'Carlos Cobrador',
                'phone' => '809-555-2001',
                'password' => Hash::make('Password123!'),
                'status' => 'active',
            ],
        );

        app(PermissionRegistrar::class)->setPermissionsTeamId($company->id);
        $user->assignRole('Cobrador');

        return Collector::query()->updateOrCreate(
            [
                'company_id' => $company->id,
                'user_id' => $user->id,
            ],
            [
                'name' => 'Carlos Cobrador',
                'phone' => '809-555-2001',
                'commission_type' => 'percentage',
                'commission_value' => 5,
                'status' => 'active',
            ],
        );
    }

    private function supportingCatalogs(Company $company): void
    {
        Zone::query()->firstOrCreate(
            ['company_id' => $company->id, 'name' => 'Zona Centro'],
            ['description' => 'Zona demo para rutas urbanas.'],
        );

        ExpenseCategory::query()->firstOrCreate([
            'company_id' => $company->id,
            'name' => 'Transporte y combustible',
        ]);
    }

    /**
     * @param array{monthly:Client,weekly:Client,dailyLate:Client} $clients
     */
    private function route(Company $company, Collector $collector, array $clients): void
    {
        $zone = Zone::query()
            ->where('company_id', $company->id)
            ->where('name', 'Zona Centro')
            ->first();

        $route = LendingRoute::query()->updateOrCreate(
            ['company_id' => $company->id, 'name' => 'Ruta Centro y Este'],
            [
                'zone_id' => $zone?->id,
                'collector_id' => $collector->id,
                'description' => 'Ruta demo para probar mapa, cobros y mora por cliente.',
                'status' => 'active',
            ],
        );

        $route->clients()->sync([
            $clients['monthly']->id => ['order_number' => 1],
            $clients['dailyLate']->id => ['order_number' => 2],
            $clients['weekly']->id => ['order_number' => 3],
        ]);
    }

    /**
     * @return array{monthly:Client,weekly:Client,dailyLate:Client}
     */
    private function clients(Company $company): array
    {
        return [
            'monthly' => Client::query()->updateOrCreate(
                ['company_id' => $company->id, 'code' => 'CLI-DEMO-001'],
                [
                    'full_name' => 'María Rodríguez',
                    'identification' => '001-1234567-8',
                    'phone' => '809-555-3001',
                    'secondary_phone' => '829-555-3001',
                    'address' => 'Ensanche Naco, Santo Domingo',
                    'latitude' => 18.4834020,
                    'longitude' => -69.9312120,
                    'location_reference' => 'Cerca de Av. Tiradentes',
                    'workplace' => 'Comercial Rodríguez',
                    'monthly_income' => 85000,
                    'status' => 'active',
                    'risk_level' => 'low',
                    'notes' => 'Cliente demo con préstamo mensual al día.',
                ],
            ),
            'weekly' => Client::query()->updateOrCreate(
                ['company_id' => $company->id, 'code' => 'CLI-DEMO-002'],
                [
                    'full_name' => 'José Martínez',
                    'identification' => '001-7654321-0',
                    'phone' => '809-555-3002',
                    'address' => 'Los Mina, Santo Domingo Este',
                    'latitude' => 18.4919780,
                    'longitude' => -69.8561590,
                    'location_reference' => 'Próximo a Av. Venezuela',
                    'workplace' => 'Taller Martínez',
                    'monthly_income' => 52000,
                    'status' => 'active',
                    'risk_level' => 'medium',
                    'notes' => 'Cliente demo con préstamo semanal y primer pago registrado.',
                ],
            ),
            'dailyLate' => Client::query()->updateOrCreate(
                ['company_id' => $company->id, 'code' => 'CLI-DEMO-003'],
                [
                    'full_name' => 'Ana Pérez',
                    'identification' => '402-1122334-5',
                    'phone' => '809-555-3003',
                    'address' => 'Villa Consuelo, Santo Domingo',
                    'latitude' => 18.4765680,
                    'longitude' => -69.8984090,
                    'location_reference' => 'Zona comercial de Villa Consuelo',
                    'workplace' => 'Colmado Ana',
                    'monthly_income' => 45000,
                    'status' => 'active',
                    'risk_level' => 'high',
                    'notes' => 'Cliente demo con préstamo diario atrasado para probar mora.',
                ],
            ),
        ];
    }
}
