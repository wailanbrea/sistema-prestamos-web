<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->string('allocation_mode', 40)->nullable()->after('payment_method');
            $table->foreignId('target_installment_id')->nullable()->after('allocation_mode')->constrained('loan_installments')->nullOnDelete();
            $table->string('excess_action', 30)->nullable()->after('target_installment_id');

            $table->index(['company_id', 'allocation_mode']);
        });

        DB::table('payments')
            ->whereNull('allocation_mode')
            ->where('capital_prepaid', '>', 0)
            ->update(['allocation_mode' => 'current_plus_capital']);

        DB::table('payments')
            ->whereNull('allocation_mode')
            ->where('principal_paid', '>', 0)
            ->where('interest_paid', '<=', 0)
            ->where('late_fee_paid', '<=', 0)
            ->update(['allocation_mode' => 'principal_only']);

        DB::table('payments')
            ->whereNull('allocation_mode')
            ->where('interest_paid', '>', 0)
            ->where('principal_paid', '<=', 0)
            ->where('late_fee_paid', '<=', 0)
            ->update(['allocation_mode' => 'interest_only']);

        DB::table('payments')
            ->whereNull('allocation_mode')
            ->where('principal_paid', '>', 0)
            ->where('interest_paid', '>', 0)
            ->where('late_fee_paid', '<=', 0)
            ->update(['allocation_mode' => 'principal_and_interest']);

        DB::table('payments')
            ->whereNull('allocation_mode')
            ->update(['allocation_mode' => 'auto']);
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropForeign(['target_installment_id']);
            $table->dropIndex(['company_id', 'allocation_mode']);
            $table->dropColumn(['allocation_mode', 'target_installment_id', 'excess_action']);
        });
    }
};
