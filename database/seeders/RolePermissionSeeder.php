<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'dashboard.view',
            'clients.view',
            'clients.create',
            'clients.update',
            'clients.delete',
            'quotes.manage',
            'quotes.convert',
            'quotes.delete',
            'loans.view',
            'loans.create',
            'loans.approve',
            'loans.update',
            'payments.create',
            'payments.cancel',
            'collectors.manage',
            'routes.manage',
            'expenses.manage',
            'cash.view',
            'reports.view',
            'documents.generate',
            'legal.manage',
            'settings.manage',
            'users.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::query()->firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        $rolePermissions = [
            'Administrador' => $permissions,
            'Supervisor' => [
                'dashboard.view',
                'clients.view',
                'clients.create',
                'clients.update',
                'quotes.manage',
                'quotes.convert',
                'loans.view',
                'loans.create',
                'loans.approve',
                'loans.update',
                'collectors.manage',
                'routes.manage',
                'reports.view',
                'documents.generate',
            ],
            'Cobrador' => [
                'dashboard.view',
                'clients.view',
                'loans.view',
                'payments.create',
                'documents.generate',
            ],
            'Caja/Contabilidad' => [
                'dashboard.view',
                'loans.view',
                'payments.create',
                'payments.cancel',
                'expenses.manage',
                'cash.view',
                'reports.view',
                'documents.generate',
            ],
            'Legal' => [
                'clients.view',
                'loans.view',
                'reports.view',
                'documents.generate',
                'legal.manage',
            ],
        ];

        foreach ($rolePermissions as $roleName => $rolePermissionNames) {
            $role = Role::query()->firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);
            $role->syncPermissions($rolePermissionNames);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
