<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Roles\StoreRoleRequest;
use App\Http\Requests\Roles\UpdateRoleRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleController extends Controller
{
    /** Roles que nunca se pueden eliminar ni quedar sin todos los permisos. */
    private const PROTECTED_ROLES = ['Administrador'];

    public function index(): View
    {
        return view('roles.index', [
            'roles' => $this->companyRolesQuery()
                ->withCount(['permissions', 'users'])
                ->orderBy('name')
                ->get()
                ->map(function (Role $role): Role {
                    $role->is_system = $role->company_id === null;
                    $role->is_protected = in_array($role->name, self::PROTECTED_ROLES, true);

                    return $role;
                }),
        ]);
    }

    public function create(): View
    {
        return view('roles.create', [
            'permissionGroups' => $this->permissionGroups(),
            'selectedPermissions' => [],
        ]);
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $companyId = (int) $request->user()->company_id;
        app(PermissionRegistrar::class)->setPermissionsTeamId($companyId);

        $role = Role::create([
            'name' => $request->validated('name'),
            'guard_name' => 'web',
            'company_id' => $companyId,
        ]);

        $role->syncPermissions($request->validated('permissions') ?? []);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()
            ->route('roles.index')
            ->with('status', 'Rol creado correctamente.');
    }

    public function edit(int $role): View
    {
        $model = $this->findCompanyRole($role);
        $model->load('permissions');

        return view('roles.edit', [
            'role' => $model,
            'permissionGroups' => $this->permissionGroups(),
            'selectedPermissions' => $model->permissions->pluck('name')->all(),
            'isProtected' => in_array($model->name, self::PROTECTED_ROLES, true),
            'isSystem' => $model->company_id === null,
        ]);
    }

    public function update(UpdateRoleRequest $request, int $role): RedirectResponse
    {
        $model = $this->findCompanyRole($role);

        // El Administrador siempre conserva todos los permisos.
        $permissions = in_array($model->name, self::PROTECTED_ROLES, true)
            ? Permission::query()->pluck('name')->all()
            : ($request->validated('permissions') ?? []);

        $model->syncPermissions($permissions);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()
            ->route('roles.index')
            ->with('status', 'Permisos del rol actualizados correctamente.');
    }

    public function duplicate(int $role): RedirectResponse
    {
        $source = $this->findCompanyRole($role);
        $companyId = (int) request()->user()->company_id;
        app(PermissionRegistrar::class)->setPermissionsTeamId($companyId);

        $name = $this->uniqueName($source->name.' (copia)', $companyId);

        $copy = Role::create([
            'name' => $name,
            'guard_name' => 'web',
            'company_id' => $companyId,
        ]);
        $copy->syncPermissions($source->permissions->pluck('name')->all());
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()
            ->route('roles.index')
            ->with('status', "Rol duplicado como «{$name}».");
    }

    public function destroy(int $role): RedirectResponse
    {
        $model = $this->findCompanyRole($role);

        if ($model->company_id === null || in_array($model->name, self::PROTECTED_ROLES, true)) {
            return back()->withErrors(['role' => 'Este rol del sistema no se puede eliminar.']);
        }

        if ($model->users()->count() > 0) {
            return back()->withErrors(['role' => 'No puedes eliminar un rol que tiene usuarios asignados. Reasigna esos usuarios primero.']);
        }

        $model->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()
            ->route('roles.index')
            ->with('status', 'Rol eliminado correctamente.');
    }

    /**
     * Roles visibles para la empresa: roles del sistema (sin empresa) + roles propios.
     */
    private function companyRolesQuery()
    {
        $companyId = (int) request()->user()->company_id;

        return Role::query()->where(function ($query) use ($companyId): void {
            $query->whereNull('company_id')->orWhere('company_id', $companyId);
        });
    }

    private function findCompanyRole(int $role): Role
    {
        return $this->companyRolesQuery()->whereKey($role)->firstOrFail();
    }

    private function uniqueName(string $base, int $companyId): string
    {
        $name = $base;
        $i = 2;
        while (Role::query()->where('company_id', $companyId)->where('name', $name)->where('guard_name', 'web')->exists()) {
            $name = $base.' '.$i++;
        }

        return $name;
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
            'loans.delete' => 'Eliminar prestamos',
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
