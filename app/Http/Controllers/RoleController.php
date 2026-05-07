<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleController extends Controller
{
    public function index(): View
    {
        return view('roles.index', [
            'roles' => Role::query()
                ->withCount('permissions')
                ->orderBy('name')
                ->get(),
            'permissionGroups' => $this->permissionGroups(),
        ]);
    }

    public function edit(int $role): View
    {
        $model = Role::query()->with('permissions')->whereKey($role)->firstOrFail();

        return view('roles.edit', [
            'role' => $model,
            'permissionGroups' => $this->permissionGroups(),
            'selectedPermissions' => $model->permissions->pluck('name')->all(),
        ]);
    }

    public function update(Request $request, int $role): RedirectResponse
    {
        $model = Role::query()->whereKey($role)->firstOrFail();
        $permissionNames = Permission::query()->pluck('name')->all();

        $validated = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in($permissionNames)],
        ]);

        $model->syncPermissions($validated['permissions'] ?? []);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()
            ->route('roles.index')
            ->with('status', 'Permisos del rol actualizados correctamente.');
    }

    /**
     * @return array<string,array<int,array{name:string,label:string,screen:string}>>
     */
    private function permissionGroups(): array
    {
        $screenLabels = collect(config('navigation.sections', []))
            ->flatMap(fn (array $section): array => $section['items'] ?? [])
            ->mapWithKeys(fn (array $item): array => [$item['permission'] => $item['label']])
            ->all();

        $labels = [
            'clients.create' => 'Crear clientes',
            'clients.update' => 'Editar clientes',
            'clients.delete' => 'Eliminar clientes',
            'quotes.manage' => 'Gestionar cotizaciones',
            'quotes.convert' => 'Convertir cotizaciones',
            'quotes.delete' => 'Eliminar cotizaciones',
            'loans.create' => 'Crear prestamos',
            'loans.approve' => 'Aprobar prestamos',
            'loans.update' => 'Editar prestamos',
            'payments.cancel' => 'Anular cobros',
            'legal.manage' => 'Gestion legal',
            'users.manage' => 'Usuarios y roles',
        ];

        return Permission::query()
            ->orderBy('name')
            ->get()
            ->groupBy(fn (Permission $permission): string => str($permission->name)->before('.')->headline()->toString())
            ->map(fn ($permissions) => $permissions
                ->map(fn (Permission $permission): array => [
                    'name' => $permission->name,
                    'label' => $labels[$permission->name] ?? $screenLabels[$permission->name] ?? $permission->name,
                    'screen' => $screenLabels[$permission->name] ?? 'Accion interna',
                ])
                ->values()
                ->all())
            ->all();
    }
}
