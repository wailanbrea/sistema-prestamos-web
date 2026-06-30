<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loan_installments', function (Blueprint $table): void {
            $table->timestamp('late_fee_waived_at')->nullable()->after('paid_late_fee');
            $table->foreignId('late_fee_waived_by')->nullable()->after('late_fee_waived_at')->constrained('users')->nullOnDelete();
            $table->string('late_fee_waived_reason', 500)->nullable()->after('late_fee_waived_by');
        });
    }

    public function down(): void
    {
        Schema::table('loan_installments', function (Blueprint $table): void {
            $table->dropForeign(['late_fee_waived_by']);
            $table->dropColumn(['late_fee_waived_at', 'late_fee_waived_by', 'late_fee_waived_reason']);
        });
    }
};
