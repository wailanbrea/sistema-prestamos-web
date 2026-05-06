<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);

        $company = Company::query()->firstOrCreate(
            ['email' => 'admin@sistemaprestamista.local'],
            [
                'name' => 'Empresa Demo',
                'phone' => '809-000-0000',
                'status' => 'active',
            ],
        );

        $company->settings()->firstOrCreate([]);

        $user = User::query()->firstOrCreate([
            'email' => 'admin@sistemaprestamista.local',
        ], [
            'company_id' => $company->id,
            'name' => 'Administrador Demo',
            'phone' => '809-000-0000',
            'password' => Hash::make('Password123!'),
            'status' => 'active',
        ]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($company->id);
        $user->assignRole('Administrador');

        $this->call(DemoLoanPortfolioSeeder::class);
    }
}
