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
        Schema::table('company_settings', function (Blueprint $table): void {
            $table->string('default_account_payable_currency', 10)
                ->default('RD$')
                ->after('default_loan_currency');
        });

        Schema::table('accounts_payable', function (Blueprint $table): void {
            $table->string('currency', 10)
                ->default('RD$')
                ->after('reference');
        });

        DB::table('company_settings')->update([
            'default_account_payable_currency' => DB::raw("COALESCE(default_loan_currency, currency, 'RD$')"),
        ]);

        $accounts = DB::table('accounts_payable')
            ->leftJoin('company_settings', 'company_settings.company_id', '=', 'accounts_payable.company_id')
            ->select('accounts_payable.id', 'company_settings.default_account_payable_currency', 'company_settings.default_loan_currency', 'company_settings.currency')
            ->get();

        foreach ($accounts as $account) {
            DB::table('accounts_payable')
                ->where('id', $account->id)
                ->update([
                    'currency' => $account->default_account_payable_currency ?: ($account->default_loan_currency ?: ($account->currency ?: 'RD$')),
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('accounts_payable', function (Blueprint $table): void {
            $table->dropColumn('currency');
        });

        Schema::table('company_settings', function (Blueprint $table): void {
            $table->dropColumn('default_account_payable_currency');
        });
    }
};
