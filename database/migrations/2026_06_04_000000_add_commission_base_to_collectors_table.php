<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('collectors', function (Blueprint $table): void {
            $table->enum('commission_base', ['payment_total', 'principal_only'])
                ->default('payment_total')
                ->after('commission_value');
        });
    }

    public function down(): void
    {
        Schema::table('collectors', function (Blueprint $table): void {
            $table->dropColumn('commission_base');
        });
    }
};
