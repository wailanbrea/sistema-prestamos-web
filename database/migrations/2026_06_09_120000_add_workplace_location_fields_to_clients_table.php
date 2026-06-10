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
            $table->text('workplace_address')->nullable()->after('workplace_phone');
            $table->decimal('workplace_latitude', 10, 7)->nullable()->after('workplace_address');
            $table->decimal('workplace_longitude', 10, 7)->nullable()->after('workplace_latitude');
            $table->string('workplace_location_reference', 180)->nullable()->after('workplace_longitude');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table): void {
            $table->dropColumn([
                'workplace_address',
                'workplace_latitude',
                'workplace_longitude',
                'workplace_location_reference',
            ]);
        });
    }
};
