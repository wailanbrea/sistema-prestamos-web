<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE cash_movements
            MODIFY type ENUM(
                'loan_disbursement',
                'payment_received',
                'accounts_payable_disbursement',
                'accounts_payable_payment',
                'expense',
                'collector_commission',
                'capital_injection',
                'capital_withdrawal',
                'adjustment'
            ) NOT NULL
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE cash_movements
            MODIFY type ENUM(
                'loan_disbursement',
                'payment_received',
                'expense',
                'collector_commission',
                'capital_injection',
                'capital_withdrawal',
                'adjustment'
            ) NOT NULL
        ");
    }
};
