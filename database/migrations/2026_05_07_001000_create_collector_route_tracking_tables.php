<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collector_route_sessions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('route_id')->constrained('routes')->cascadeOnDelete();
            $table->foreignId('collector_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['active', 'completed', 'cancelled'])->default('active');
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->timestamp('last_location_at')->nullable();
            $table->decimal('last_latitude', 10, 7)->nullable();
            $table->decimal('last_longitude', 10, 7)->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->index(['collector_id', 'status']);
            $table->index(['route_id', 'status']);
        });

        Schema::create('collector_location_points', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('collector_route_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('collector_id')->constrained()->cascadeOnDelete();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->unsignedInteger('accuracy_meters')->nullable();
            $table->unsignedTinyInteger('battery_level')->nullable();
            $table->timestamp('recorded_at');
            $table->timestamps();

            $table->index(['collector_route_session_id', 'recorded_at'], 'clp_session_recorded_idx');
            $table->index(['collector_id', 'recorded_at'], 'clp_collector_recorded_idx');
        });

        Schema::create('route_visit_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('collector_route_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('route_id')->constrained('routes')->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('expected_order');
            $table->unsignedInteger('visited_order')->nullable();
            $table->enum('status', ['visited', 'visited_out_of_order'])->default('visited');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->unsignedInteger('distance_meters');
            $table->timestamp('visited_at');
            $table->timestamps();

            $table->unique(['collector_route_session_id', 'client_id'], 'route_visit_session_client_unique');
            $table->index(['route_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('route_visit_events');
        Schema::dropIfExists('collector_location_points');
        Schema::dropIfExists('collector_route_sessions');
    }
};
