<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
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

            return;
        }

        // SQLite (tests): el enum original es TEXT + CHECK; recrear la columna
        // como string elimina el CHECK y acepta los nuevos tipos.
        Schema::table('cash_movements', function (Blueprint $table): void {
            $table->string('type', 50)->change();
        });
    }

    public function down(): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

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
