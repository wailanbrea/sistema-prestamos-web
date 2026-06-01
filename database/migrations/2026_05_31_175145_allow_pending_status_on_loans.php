<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Quita el CHECK del enum para permitir el estado 'pending' (aprobación) y futuros estados.
        Schema::table('loans', function (Blueprint $table): void {
            $table->string('status')->default('active')->change();
        });
    }

    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table): void {
            $table->enum('status', ['active', 'late', 'paid', 'refinanced', 'cancelled', 'legal', 'written_off'])->default('active')->change();
        });
    }
};
