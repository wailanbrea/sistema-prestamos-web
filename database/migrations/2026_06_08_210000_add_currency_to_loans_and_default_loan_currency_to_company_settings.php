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
            $table->string('default_loan_currency', 10)->default('RD$')->after('currency');
        });

        Schema::table('loans', function (Blueprint $table): void {
            $table->string('currency', 10)->default('RD$')->after('client_id');
        });

        DB::table('company_settings')->update([
            'default_loan_currency' => DB::raw('currency'),
        ]);

        $rows = DB::table('loans')
            ->leftJoin('company_settings', 'company_settings.company_id', '=', 'loans.company_id')
            ->select('loans.id', 'company_settings.default_loan_currency', 'company_settings.currency')
            ->get();

        foreach ($rows as $row) {
            DB::table('loans')
                ->where('id', $row->id)
                ->update([
                    'currency' => $row->default_loan_currency ?: ($row->currency ?: 'RD$'),
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table): void {
            $table->dropColumn('currency');
        });

        Schema::table('company_settings', function (Blueprint $table): void {
            $table->dropColumn('default_loan_currency');
        });
    }
};
