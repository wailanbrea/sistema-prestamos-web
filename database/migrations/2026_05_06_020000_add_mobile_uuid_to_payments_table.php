<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->uuid('mobile_uuid')->nullable()->after('receipt_number');
            $table->unique(['company_id', 'mobile_uuid']);
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropUnique(['company_id', 'mobile_uuid']);
            $table->dropColumn('mobile_uuid');
        });
    }
};
