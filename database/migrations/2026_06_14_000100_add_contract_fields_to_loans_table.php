<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loans', function (Blueprint $table): void {
            $table->boolean('contract_required')->default(false)->after('guarantee_description');
            $table->boolean('contract_signed')->default(false)->after('contract_required');
            $table->timestamp('contract_signed_at')->nullable()->after('contract_signed');
        });
    }

    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table): void {
            $table->dropColumn(['contract_required', 'contract_signed', 'contract_signed_at']);
        });
    }
};
