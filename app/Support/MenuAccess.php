<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\User;

/**
 * Lógica de visibilidad y bloqueo de menús por usuario.
 *
 * - `visible_menus` null  => el usuario ve todos los menús.
 * - `visible_menus` array => solo ve los ítems cuya ruta esté en la lista.
 *
 * Las rutas de gestión (settings/users/roles) nunca se bloquean para quien
 * tenga el permiso correspondiente, como salvaguarda contra auto-bloqueo.
 */
class MenuAccess
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function flatItems(): array
    {
        $items = [];
        foreach (config('navigation.sections', []) as $section) {
            foreach ($section['items'] ?? [] as $item) {
                $items[] = $item;
            }
        }

        return $items;
    }

    /**
     * Rutas de menú elegibles para mostrarse en el formulario de usuario.
     *
     * @return list<string>
     */
    public static function selectableRoutes(): array
    {
        return array_values(array_map(static fn (array $item): string => $item['route'], self::flatItems()));
    }

    /**
     * Rutas de menú que permite el plan/licencia de la empresa. null = todas.
     *
     * @return list<string>|null
     */
    public static function planMenuKeys(User $user): ?array
    {
        $plan = $user->company?->plan ?: 'full';
        $menus = config("plans.{$plan}.menus", '*');

        return $menus === '*' ? null : (array) $menus;
    }

    /**
     * Rutas que el usuario puede ver en el menú, combinando plan y visibilidad personal.
     * null = todas. (La salvaguarda de gestión NO aplica al menú lateral, solo oculta.)
     *
     * @return list<string>|null
     */
    public static function allowedRouteKeys(User $user): ?array
    {
        $plan = self::planMenuKeys($user);
        $personal = $user->visible_menus;

        if ($plan === null && $personal === null) {
            return null;
        }

        $all = self::selectableRoutes();
        $planSet = $plan ?? $all;
        $personalSet = $personal ?? $all;

        return array_values(array_intersect($planSet, $personalSet));
    }

    public static function isItemVisible(User $user, array $item): bool
    {
        $allowed = self::allowedRouteKeys($user);

        return $allowed === null || in_array($item['route'], $allowed, true);
    }

    /**
     * ¿El plan/licencia de la empresa incluye el menú/característica indicada?
     */
    public static function planAllowsMenu(User $user, string $route): bool
    {
        $plan = self::planMenuKeys($user);

        return $plan === null || in_array($route, $plan, true);
    }

    /**
     * ¿El plan/licencia de la empresa permite gestionar usuarios y roles?
     */
    public static function canManageUsers(User $user): bool
    {
        return self::planAllowsMenu($user, 'roles.index');
    }

    /**
     * ¿Puede el usuario acceder a la ruta dada? (para el middleware de bloqueo)
     *
     * Se evalúan dos dimensiones independientes:
     *  - Plan/licencia de la empresa: sin salvaguarda (si el plan no lo incluye, se bloquea).
     *  - Visibilidad personal del usuario: con salvaguarda (gestión accesible para no auto-bloquearse).
     */
    public static function allowsRoute(User $user, ?string $routeName): bool
    {
        if ($routeName === null) {
            return true;
        }

        // Dimensión plan (sin salvaguarda).
        if (! self::routeAllowedByKeys(self::planMenuKeys($user), $routeName, false, $user)) {
            return false;
        }

        // Dimensión personal (con salvaguarda contra auto-bloqueo).
        return self::routeAllowedByKeys($user->visible_menus, $routeName, true, $user);
    }

    /**
     * @param  list<string>|null  $keys  null = sin restricción
     */
    private static function routeAllowedByKeys(?array $keys, string $routeName, bool $withSafeguard, User $user): bool
    {
        if ($keys === null) {
            return true;
        }

        if ($withSafeguard) {
            if ($user->can('settings.manage') && self::matchesAny($routeName, ['settings.'])) {
                return true;
            }
            if ($user->can('users.manage') && self::matchesAny($routeName, ['users.', 'roles.'])) {
                return true;
            }
        }

        $allPrefixes = [];
        $visiblePrefixes = [];
        foreach (self::flatItems() as $item) {
            foreach ((array) ($item['match'] ?? []) as $prefix) {
                $allPrefixes[] = $prefix;
                if (in_array($item['route'], $keys, true)) {
                    $visiblePrefixes[] = $prefix;
                }
            }
        }

        // Rutas no gobernadas por ningún menú (p. ej. logout) se permiten.
        if (! self::matchesAny($routeName, $allPrefixes)) {
            return true;
        }

        return self::matchesAny($routeName, $visiblePrefixes);
    }

    /**
     * @param  list<string>  $prefixes
     */
    private static function matchesAny(string $routeName, array $prefixes): bool
    {
        foreach ($prefixes as $prefix) {
            if ($routeName === $prefix || str_starts_with($routeName, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
