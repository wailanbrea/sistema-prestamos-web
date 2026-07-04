<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const WITH_GERMAN = "'flat_interest','fixed_installment','capital_plus_interest','interest_only','german_amortization','french_amortization'";

    private const WITHOUT_GERMAN = "'flat_interest','fixed_installment','capital_plus_interest','interest_only','french_amortization'";

    public function up(): void
    {
        DB::statement("ALTER TABLE loan_quotes MODIFY calculation_method ENUM(".self::WITH_GERMAN.") NOT NULL");
        DB::statement("ALTER TABLE loans MODIFY calculation_method ENUM(".self::WITH_GERMAN.") NOT NULL");
        DB::statement("ALTER TABLE accounts_payable MODIFY calculation_method ENUM(".self::WITH_GERMAN.") NOT NULL DEFAULT 'flat_interest'");
    }

    public function down(): void
    {
        DB::table('loan_quotes')->where('calculation_method', 'german_amortization')->update(['calculation_method' => 'french_amortization']);
        DB::table('loans')->where('calculation_method', 'german_amortization')->update(['calculation_method' => 'french_amortization']);
        DB::table('accounts_payable')->where('calculation_method', 'german_amortization')->update(['calculation_method' => 'french_amortization']);

        DB::statement("ALTER TABLE loan_quotes MODIFY calculation_method ENUM(".self::WITHOUT_GERMAN.") NOT NULL");
        DB::statement("ALTER TABLE loans MODIFY calculation_method ENUM(".self::WITHOUT_GERMAN.") NOT NULL");
        DB::statement("ALTER TABLE accounts_payable MODIFY calculation_method ENUM(".self::WITHOUT_GERMAN.") NOT NULL DEFAULT 'flat_interest'");
    }
};
