<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        // Identidad estable del dueño del sistema (no atada al email).
        // Deliberadamente fuera de $fillable: no debe ser asignable por formularios.
        if (! Schema::hasColumn('users', 'is_system_owner')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->boolean('is_system_owner')->default(false)->after('status');
            });
        }

        // Marca como dueño al correo definido en config/system.php.
        $email = (string) config('system.owner_email');
        if ($email !== '') {
            DB::table('users')->where('email', $email)->update(['is_system_owner' => true]);
        }

        // Habilidad exclusiva del dueño (concedida vía Gate::before). Se registra
        // como permiso para que can('companies.manage-plan') de un no-dueño
        // devuelva false en vez de lanzar PermissionDoesNotExist. No se asigna
        // a ningún rol.
        Permission::query()->firstOrCreate(['name' => 'companies.manage-plan', 'guard_name' => 'web']);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'is_system_owner')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropColumn('is_system_owner');
            });
        }
    }
};
