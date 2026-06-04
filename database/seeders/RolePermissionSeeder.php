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
            'collector.access',
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
            'loans.delete',
            'payments.create',
            'payments.cancel',
            'collectors.manage',
            'routes.manage',
            'expenses.manage',
            'accounts-payable.manage',
            'cash.view',
            'reports.view',
            'documents.generate',
            'legal.manage',
            'settings.manage',
            'users.manage',
        ];

        // Habilidades exclusivas del dueño del sistema: existen como permiso pero
        // NO se asignan a ningún rol; se conceden vía Gate::before a quien tiene
        // is_system_owner. Mantenerlas fuera de $permissions evita que el rol
        // Administrador (que recibe todo $permissions) las herede.
        $ownerOnlyPermissions = [
            'companies.manage-plan',
        ];

        foreach (array_merge($permissions, $ownerOnlyPermissions) as $permission) {
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
                'loans.delete',
                'collectors.manage',
                'routes.manage',
                'accounts-payable.manage',
                'reports.view',
                'documents.generate',
            ],
            'Cobrador' => [
                'collector.access',
            ],
            'Caja/Contabilidad' => [
                'dashboard.view',
                'loans.view',
                'payments.create',
                'payments.cancel',
                'expenses.manage',
                'accounts-payable.manage',
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
