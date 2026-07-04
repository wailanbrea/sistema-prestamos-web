<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const WITH_GERMAN = "'flat_interest','fixed_installment','capital_plus_interest','interest_only','german_amortization','french_amortization'";

    private const WITHOUT_GERMAN = "'flat_interest','fixed_installment','capital_plus_interest','interest_only','french_amortization'";

    public function up(): void
    {
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE loan_quotes MODIFY calculation_method ENUM(".self::WITH_GERMAN.") NOT NULL");
            DB::statement("ALTER TABLE loans MODIFY calculation_method ENUM(".self::WITH_GERMAN.") NOT NULL");
            DB::statement("ALTER TABLE accounts_payable MODIFY calculation_method ENUM(".self::WITH_GERMAN.") NOT NULL DEFAULT 'flat_interest'");

            return;
        }

        // SQLite (tests): el enum original es TEXT + CHECK; recrear la columna
        // como string elimina el CHECK y acepta los nuevos metodos.
        Schema::table('loan_quotes', function (Blueprint $table): void {
            $table->string('calculation_method', 50)->change();
        });
        Schema::table('loans', function (Blueprint $table): void {
            $table->string('calculation_method', 50)->change();
        });
        Schema::table('accounts_payable', function (Blueprint $table): void {
            $table->string('calculation_method', 50)->default('flat_interest')->change();
        });
    }

    public function down(): void
    {
        DB::table('loan_quotes')->where('calculation_method', 'german_amortization')->update(['calculation_method' => 'french_amortization']);
        DB::table('loans')->where('calculation_method', 'german_amortization')->update(['calculation_method' => 'french_amortization']);
        DB::table('accounts_payable')->where('calculation_method', 'german_amortization')->update(['calculation_method' => 'french_amortization']);

        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        DB::statement("ALTER TABLE loan_quotes MODIFY calculation_method ENUM(".self::WITHOUT_GERMAN.") NOT NULL");
        DB::statement("ALTER TABLE loans MODIFY calculation_method ENUM(".self::WITHOUT_GERMAN.") NOT NULL");
        DB::statement("ALTER TABLE accounts_payable MODIFY calculation_method ENUM(".self::WITHOUT_GERMAN.") NOT NULL DEFAULT 'flat_interest'");
    }
};
