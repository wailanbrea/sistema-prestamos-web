<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

/**
 * Global scope de multi-empresa (defensa en profundidad).
 *
 * Filtra automáticamente por la empresa del usuario autenticado para que ninguna
 * consulta pueda ver datos de otra empresa aunque olvide el `->forCompany()` explícito.
 *
 * Solo aplica cuando hay un usuario autenticado con `company_id`. En consola
 * (comandos agendados, seeders), colas sin usuario y flujos públicos no
 * autenticados NO aplica, por lo que esos flujos siguen operando cross-empresa
 * mediante su filtro explícito (p. ej. `loans:refresh-late-status`).
 *
 * Para un caso legítimo que deba saltar el filtro:
 *   Modelo::withoutGlobalScope(CompanyScope::class)->...
 */
class CompanyScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $companyId = $this->resolveCompanyId();

        if ($companyId === null) {
            return;
        }

        $builder->where($model->getTable().'.company_id', $companyId);
    }

    private function resolveCompanyId(): ?int
    {
        if (! Auth::hasUser() && ! Auth::check()) {
            return null;
        }

        /** @var Authenticatable|null $user */
        $user = Auth::user();
        $companyId = $user?->getAttribute('company_id');

        return $companyId ? (int) $companyId : null;
    }
}
