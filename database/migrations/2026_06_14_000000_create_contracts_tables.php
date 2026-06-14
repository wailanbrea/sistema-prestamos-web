<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contracts', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('loan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->restrictOnDelete();
            $table->foreignId('document_id')->nullable()->constrained()->nullOnDelete();
            $table->string('contract_number', 50);
            $table->enum('contract_type', ['loan_contract', 'promissory_note', 'disbursement_receipt', 'settlement_letter'])->default('loan_contract');
            $table->enum('status', ['draft', 'generated', 'sent', 'viewed', 'signed', 'rejected', 'cancelled', 'expired'])->default('draft');
            $table->unsignedInteger('version')->default(1);
            $table->string('hash_sha256', 64)->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('viewed_at')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'contract_number']);
            $table->index(['company_id', 'status']);
            $table->index('loan_id');
        });

        Schema::create('contract_signatures', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->string('signature_image_path');
            $table->string('signer_name', 180);
            $table->string('ip_address', 50)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('device_type', 30)->nullable();
            $table->string('browser', 60)->nullable();
            $table->string('platform', 60)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->boolean('accepted_terms')->default(false);
            $table->boolean('accepted_legal')->default(false);
            $table->timestamp('signed_at')->nullable();
            $table->timestamps();

            $table->index('contract_id');
        });

        Schema::create('contract_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->string('event_type', 60);
            $table->text('description')->nullable();
            $table->string('ip_address', 50)->nullable();
            $table->json('metadata_json')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['contract_id', 'event_type']);
        });

        Schema::create('contract_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->foreignId('document_id')->nullable()->constrained()->nullOnDelete();
            $table->string('pdf_path');
            $table->string('hash_sha256', 64)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['contract_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_versions');
        Schema::dropIfExists('contract_events');
        Schema::dropIfExists('contract_signatures');
        Schema::dropIfExists('contracts');
    }
};
