<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_settings', function (Blueprint $table): void {
            $table->json('enabled_loan_calculation_methods')->nullable()->after('default_account_payable_currency');
            $table->json('enabled_payment_allocation_modes')->nullable()->after('enabled_loan_calculation_methods');
        });
    }

    public function down(): void
    {
        Schema::table('company_settings', function (Blueprint $table): void {
            $table->dropColumn(['enabled_loan_calculation_methods', 'enabled_payment_allocation_modes']);
        });
    }
};
