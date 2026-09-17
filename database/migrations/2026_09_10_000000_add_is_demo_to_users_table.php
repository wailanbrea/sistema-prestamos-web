<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('is_demo')->default(false)->after('is_system_owner');
        });

        // Marcar las cuentas demo existentes como cuentas demo
        DB::table('users')
            ->whereIn('email', [
                'demo@prestamista.bsolutions.dev',
                'cobrador@sistemaprestamista.local',
            ])
            ->where('is_system_owner', false)
            ->update(['is_demo' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('is_demo');
        });
    }
};
