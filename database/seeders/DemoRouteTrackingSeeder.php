<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Collector;
use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\Loan;
use App\Models\Route as LendingRoute;
use App\Models\User;
use App\Models\Zone;
use App\Services\Loans\LoanService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

class DemoRouteTrackingSeeder extends Seeder
{
    private const COMPANY_EMAIL = 'admin@sistemaprestamista.local';
    private const ADMIN_EMAIL = 'admin@sistemaprestamista.local';
    private const COLLECTOR_EMAIL = 'cobrador@sistemaprestamista.local';
    private const LOAN_MARKER = 'DEMO_ROUTE_TRACKING_V1';

    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);

        /** @var LoanService $loanService */
        $loanService = app(LoanService::class);

        DB::transaction(function () use ($loanService): void {
            $company = $this->company();
            $admin = $this->admin($company);
            $collector = $this->collector($company);
            $clients = $this->clients($company);

            $this->route($company, $collector, $clients);
            $this->loans($company, $admin, $collector, $clients, $loanService);
        });
    }

    private function company(): Company
    {
        $company = Company::query()->firstOrCreate(
            ['email' => self::COMPANY_EMAIL],
            [
                'name' => 'Prestamista Demo RD',
                'rnc' => '131000001',
                'phone' => '809-555-1000',
                'address' => 'Av. Winston Churchill, Santo Domingo',
                'status' => 'active',
                'plan' => 'prestamista',
            ],
        );

        $company->forceFill([
            'name' => $company->name ?: 'Prestamista Demo RD',
            'phone' => $company->phone ?: '809-555-1000',
            'status' => 'active',
            'plan' => $company->plan ?: 'prestamista',
        ])->save();

        $settings = [
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
        ];

        if (Schema::hasColumn('company_settings', 'route_visit_radius_meters')) {
            $settings['route_visit_radius_meters'] = 75;
        }

        CompanySetting::query()->updateOrCreate(['company_id' => $company->id], $settings);

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

        $collectorData = [
            'name' => 'Carlos Cobrador',
            'phone' => '809-555-2001',
            'commission_type' => 'percentage',
            'commission_value' => 5,
            'status' => 'active',
        ];

        if (Schema::hasColumn('collectors', 'commission_base')) {
            $collectorData['commission_base'] = 'payment_total';
        }

        return Collector::query()->updateOrCreate(
            [
                'company_id' => $company->id,
                'user_id' => $user->id,
            ],
            $collectorData,
        );
    }

    /**
     * @return array{monthly: Client, late: Client, weekly: Client}
     */
    private function clients(Company $company): array
    {
        return [
            'monthly' => Client::query()->updateOrCreate(
                ['company_id' => $company->id, 'code' => 'CLI-DEMO-001'],
                [
                    'full_name' => 'Maria Rodriguez',
                    'identification' => '001-1234567-8',
                    'phone' => '809-555-3001',
                    'secondary_phone' => '829-555-3001',
                    'address' => 'Ensanche Naco, Santo Domingo',
                    'latitude' => 18.4834020,
                    'longitude' => -69.9312120,
                    'location_reference' => 'Cerca de Av. Tiradentes',
                    'workplace' => 'Comercial Rodriguez',
                    'monthly_income' => 85000,
                    'status' => 'active',
                    'risk_level' => 'low',
                    'notes' => 'Cliente demo para ruta GPS: prestamo mensual activo.',
                ],
            ),
            'late' => Client::query()->updateOrCreate(
                ['company_id' => $company->id, 'code' => 'CLI-DEMO-003'],
                [
                    'full_name' => 'Ana Perez',
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
                    'notes' => 'Cliente demo para ruta GPS: prestamo diario atrasado.',
                ],
            ),
            'weekly' => Client::query()->updateOrCreate(
                ['company_id' => $company->id, 'code' => 'CLI-DEMO-002'],
                [
                    'full_name' => 'Jose Martinez',
                    'identification' => '001-7654321-0',
                    'phone' => '809-555-3002',
                    'address' => 'Los Mina, Santo Domingo Este',
                    'latitude' => 18.4919780,
                    'longitude' => -69.8561590,
                    'location_reference' => 'Proximo a Av. Venezuela',
                    'workplace' => 'Taller Martinez',
                    'monthly_income' => 52000,
                    'status' => 'active',
                    'risk_level' => 'medium',
                    'notes' => 'Cliente demo para ruta GPS: prestamo semanal activo.',
                ],
            ),
        ];
    }

    /**
     * @param array{monthly: Client, late: Client, weekly: Client} $clients
     */
    private function route(Company $company, Collector $collector, array $clients): void
    {
        $zone = Zone::query()->updateOrCreate(
            ['company_id' => $company->id, 'name' => 'Zona Centro y Este'],
            ['description' => 'Zona demo para validar rutas GPS y seguimiento de cobradores.'],
        );

        $route = LendingRoute::query()->updateOrCreate(
            ['company_id' => $company->id, 'name' => 'Ruta Centro y Este'],
            [
                'zone_id' => $zone->id,
                'collector_id' => $collector->id,
                'description' => 'Ruta demo para iniciar en Android y monitorear ubicacion en la web.',
                'status' => 'active',
            ],
        );

        $route->clients()->sync([
            $clients['monthly']->id => ['order_number' => 1],
            $clients['late']->id => ['order_number' => 2],
            $clients['weekly']->id => ['order_number' => 3],
        ]);
    }

    /**
     * @param array{monthly: Client, late: Client, weekly: Client} $clients
     */
    private function loans(Company $company, User $admin, Collector $collector, array $clients, LoanService $loanService): void
    {
        $this->createLoanIfMissing($company, $admin, $collector, $clients['monthly'], $loanService, [
            'principal_amount' => 120000,
            'interest_rate' => 12,
            'payment_frequency' => 'monthly',
            'calculation_method' => 'flat_interest',
            'term_quantity' => 6,
            'late_fee_type' => 'daily_fixed',
            'late_fee_value' => 150,
            'start_date' => '2026-05-06',
            'first_payment_date' => '2026-06-06',
            'guarantee_description' => 'Motor Honda Lead 2023 y pagare notarial.',
            'notes' => self::LOAN_MARKER.' mensual activo para Maria Rodriguez.',
        ]);

        $this->createLoanIfMissing($company, $admin, $collector, $clients['late'], $loanService, [
            'principal_amount' => 15000,
            'interest_rate' => 10,
            'payment_frequency' => 'daily',
            'calculation_method' => 'flat_interest',
            'term_quantity' => 10,
            'late_fee_type' => 'daily_fixed',
            'late_fee_value' => 75,
            'start_date' => '2026-04-20',
            'first_payment_date' => '2026-04-21',
            'guarantee_description' => 'Nevera comercial y contrato firmado.',
            'notes' => self::LOAN_MARKER.' diario atrasado para Ana Perez.',
        ]);

        $this->createLoanIfMissing($company, $admin, $collector, $clients['weekly'], $loanService, [
            'principal_amount' => 25000,
            'interest_rate' => 5,
            'payment_frequency' => 'weekly',
            'calculation_method' => 'capital_plus_interest',
            'term_quantity' => 8,
            'late_fee_type' => 'fixed',
            'late_fee_value' => 300,
            'start_date' => '2026-04-29',
            'first_payment_date' => '2026-05-06',
            'guarantee_description' => 'Garantia solidaria con dos referencias.',
            'notes' => self::LOAN_MARKER.' semanal activo para Jose Martinez.',
        ]);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function createLoanIfMissing(
        Company $company,
        User $admin,
        Collector $collector,
        Client $client,
        LoanService $loanService,
        array $data,
    ): void {
        $exists = Loan::query()
            ->forCompany((int) $company->id)
            ->where('client_id', $client->id)
            ->where('collector_id', $collector->id)
            ->where('notes', 'like', '%'.self::LOAN_MARKER.'%')
            ->exists();

        if ($exists) {
            return;
        }

        $loanService->create(
            companyId: (int) $company->id,
            userId: (int) $admin->id,
            data: [
                ...$data,
                'client_id' => $client->id,
                'collector_id' => $collector->id,
                'interest_type' => 'fixed',
                'allows_capital_prepayment' => true,
            ],
        );
    }
}
