<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_settings', function (Blueprint $table): void {
            $table->string('default_map_address', 2000)->nullable()->after('route_visit_radius_meters');
            $table->decimal('default_map_latitude', 10, 7)->nullable()->after('default_map_address');
            $table->decimal('default_map_longitude', 10, 7)->nullable()->after('default_map_latitude');
        });
    }

    public function down(): void
    {
        Schema::table('company_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'default_map_address',
                'default_map_latitude',
                'default_map_longitude',
            ]);
        });
    }
};
