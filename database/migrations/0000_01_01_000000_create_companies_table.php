<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 150);
            $table->string('rnc', 50)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('email', 150)->nullable();
            $table->text('address')->nullable();
            $table->string('logo')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();

            $table->index('status');
        });

        Schema::create('company_settings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('currency', 10)->default('RD$');
            $table->decimal('default_interest_rate', 8, 4)->default(0);
            $table->enum('default_late_fee_type', ['none', 'fixed', 'daily_percentage', 'daily_fixed'])->default('none');
            $table->decimal('default_late_fee_value', 12, 2)->default(0);
            $table->string('receipt_prefix', 20)->default('REC');
            $table->string('loan_prefix', 20)->default('PRE');
            $table->string('quote_prefix', 20)->default('COT');
            $table->boolean('allow_partial_payments')->default(true);
            $table->boolean('allow_payment_cancellation')->default(true);
            $table->boolean('require_approval_for_loans')->default(true);
            $table->boolean('exclude_sundays_for_daily_loans')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_settings');
        Schema::dropIfExists('companies');
    }
};
