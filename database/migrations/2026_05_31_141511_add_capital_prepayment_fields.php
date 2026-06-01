<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loans', function (Blueprint $table): void {
            $table->boolean('allows_capital_prepayment')->default(true)->after('late_fee_value');
        });

        Schema::table('payments', function (Blueprint $table): void {
            $table->decimal('capital_prepaid', 12, 2)->default(0)->after('discount');
            $table->decimal('change_given', 12, 2)->default(0)->after('capital_prepaid');
        });
    }

    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table): void {
            $table->dropColumn('allows_capital_prepayment');
        });

        Schema::table('payments', function (Blueprint $table): void {
            $table->dropColumn(['capital_prepaid', 'change_given']);
        });
    }
};
