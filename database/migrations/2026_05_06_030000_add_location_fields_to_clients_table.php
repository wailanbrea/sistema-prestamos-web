<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table): void {
            $table->decimal('latitude', 10, 7)->nullable()->after('address');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->string('location_reference', 180)->nullable()->after('longitude');

            $table->index(['company_id', 'latitude', 'longitude']);
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table): void {
            $table->dropIndex(['company_id', 'latitude', 'longitude']);
            $table->dropColumn(['latitude', 'longitude', 'location_reference']);
        });
    }
};
