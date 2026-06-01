<?php

declare(strict_types=1);

namespace App\Services\Users;

use App\Models\User;
use App\Services\Audit\AuditService;
use App\Support\MenuAccess;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\PermissionRegistrar;

class UserService
{
    public function __construct(private readonly AuditService $auditService) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateForCompany(int $companyId, array $filters = []): LengthAwarePaginator
    {
        return User::query()
            ->with('roles:id,name')
            ->where('company_id', $companyId)
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $nested) use ($search): void {
                    $nested->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->latest('id')
            ->paginate(15)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(int $companyId, array $data, int $createdBy): User
    {
        return DB::transaction(function () use ($companyId, $data, $createdBy): User {
            $role = $data['role'];
            unset($data['role'], $data['password_confirmation']);
            $data['company_id'] = $companyId;
            $data['visible_menus'] = $this->normalizeVisibleMenus($data['visible_menus'] ?? []);

            $user = User::query()->create($data);
            app(PermissionRegistrar::class)->setPermissionsTeamId($companyId);
            $user->assignRole($role);

            $this->auditService->record(
                action: 'user_created',
                module: 'users',
                companyId: $companyId,
                userId: $createdBy,
                auditable: $user,
                description: "Usuario creado: {$user->email}.",
                newValues: $user->fresh()?->toArray(),
            );

            return $user;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $user, array $data, int $updatedBy): User
    {
        if ($user->id === $updatedBy && $data['status'] !== 'active') {
            throw ValidationException::withMessages([
                'status' => 'No puedes bloquear tu propio usuario.',
            ]);
        }

        return DB::transaction(function () use ($user, $data, $updatedBy): User {
            $role = $data['role'];
            unset($data['role'], $data['password_confirmation']);

            if (blank($data['password'] ?? null)) {
                unset($data['password']);
            }

            $data['visible_menus'] = $this->normalizeVisibleMenus($data['visible_menus'] ?? []);

            $oldValues = $user->toArray();
            $user->update($data);
            app(PermissionRegistrar::class)->setPermissionsTeamId((int) $user->company_id);
            $user->syncRoles([$role]);

            $this->auditService->record(
                action: 'user_updated',
                module: 'users',
                companyId: (int) $user->company_id,
                userId: $updatedBy,
                auditable: $user,
                description: "Usuario actualizado: {$user->email}.",
                oldValues: $oldValues,
                newValues: $user->fresh()?->toArray(),
            );

            return $user->refresh();
        });
    }

    public function findForCompany(int $companyId, int $userId): User
    {
        return User::query()
            ->where('company_id', $companyId)
            ->whereKey($userId)
            ->firstOrFail();
    }

    /**
     * Normaliza la selección de menús: todos marcados => null (ve todo); subconjunto => array.
     *
     * @param  array<int, string>  $selected
     * @return array<int, string>|null
     */
    private function normalizeVisibleMenus(array $selected): ?array
    {
        $all = MenuAccess::selectableRoutes();
        $selected = array_values(array_intersect($all, $selected));

        sort($all);
        $sortedSelected = $selected;
        sort($sortedSelected);

        return $sortedSelected === $all ? null : $selected;
    }
}
