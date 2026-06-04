<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_registration_links', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('used_client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->string('token', 80)->unique();
            $table->string('recipient_name', 180)->nullable();
            $table->string('recipient_phone', 50)->nullable();
            $table->timestamp('used_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'created_at']);
            $table->index(['company_id', 'recipient_phone']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_registration_links');
    }
};
