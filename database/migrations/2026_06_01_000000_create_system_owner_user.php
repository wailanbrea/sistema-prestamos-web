<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Crea (o actualiza) el usuario dueño/creador del sistema, el único que puede
     * cambiar el tipo de licencia de las empresas. Idempotente: se puede correr en
     * cualquier despliegue. El correo se toma de config/system.php.
     */
    public function up(): void
    {
        $email = (string) config('system.owner_email');
        if ($email === '') {
            return;
        }

        // El dueño se asocia a la primera empresa para tener contexto de equipo
        // (roles de spatie/laravel-permission usan teams por company_id).
        $company = Company::query()->orderBy('id')->first();
        if (! $company) {
            return;
        }

        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'company_id' => $company->id,
                'name' => 'Wailan — Dueño del sistema',
                'password' => Hash::make('driffgraff09'),
                'status' => 'active',
            ],
        );

        app(PermissionRegistrar::class)->setPermissionsTeamId((int) $company->id);
        if (! $user->hasRole('Administrador')) {
            $user->assignRole('Administrador');
        }
    }

    public function down(): void
    {
        $email = (string) config('system.owner_email');
        if ($email !== '') {
            User::query()->where('email', $email)->delete();
        }
    }
};
