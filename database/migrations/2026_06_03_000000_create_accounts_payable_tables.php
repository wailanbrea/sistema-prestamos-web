<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('creditors', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name', 180);
            $table->string('document', 50)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('email', 150)->nullable();
            $table->text('address')->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamps();

            $table->index(['company_id', 'name']);
            $table->index(['company_id', 'status']);
        });

        Schema::create('accounts_payable', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('creditor_id')->constrained()->cascadeOnDelete();
            $table->string('reference', 50)->unique();
            $table->decimal('principal_amount', 12, 2);
            $table->decimal('interest_rate', 8, 4);
            $table->enum('interest_type', ['fixed', 'compound', 'amortized'])->default('fixed');
            $table->enum('payment_frequency', ['daily', 'weekly', 'biweekly', 'monthly'])->default('monthly');
            $table->enum('calculation_method', ['flat_interest', 'fixed_installment', 'capital_plus_interest', 'interest_only', 'french_amortization'])->default('flat_interest');
            $table->unsignedInteger('term_quantity');
            $table->decimal('installment_amount', 12, 2);
            $table->decimal('total_interest', 12, 2);
            $table->decimal('total_amount', 12, 2);
            $table->decimal('paid_principal', 12, 2)->default(0);
            $table->decimal('paid_interest', 12, 2)->default(0);
            $table->decimal('paid_late_fee', 12, 2)->default(0);
            $table->decimal('remaining_balance', 12, 2);
            $table->enum('late_fee_type', ['none', 'fixed', 'daily_fixed'])->default('none');
            $table->decimal('late_fee_value', 12, 2)->default(0);
            $table->date('disbursement_date');
            $table->date('first_payment_date');
            $table->date('end_date')->nullable();
            $table->enum('status', ['active', 'late', 'paid', 'cancelled'])->default('active');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'creditor_id']);
        });

        Schema::create('account_payable_installments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('account_payable_id')->constrained('accounts_payable')->cascadeOnDelete();
            $table->unsignedInteger('installment_number');
            $table->date('due_date');
            $table->decimal('principal_amount', 12, 2);
            $table->decimal('interest_amount', 12, 2);
            $table->decimal('installment_amount', 12, 2);
            $table->decimal('late_fee', 12, 2)->default(0);
            $table->decimal('paid_principal', 12, 2)->default(0);
            $table->decimal('paid_interest', 12, 2)->default(0);
            $table->decimal('paid_late_fee', 12, 2)->default(0);
            $table->decimal('total_paid', 12, 2)->default(0);
            $table->unsignedInteger('days_late')->default(0);
            $table->enum('status', ['pending', 'partial', 'late', 'paid', 'cancelled'])->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->unique(['account_payable_id', 'installment_number'], 'ap_installments_number_unique');
            $table->index(['account_payable_id', 'status']);
        });

        Schema::create('account_payable_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_payable_id')->constrained('accounts_payable')->cascadeOnDelete();
            $table->foreignId('creditor_id')->constrained('creditors')->cascadeOnDelete();
            $table->string('payment_number', 50)->unique();
            $table->date('payment_date');
            $table->decimal('amount', 12, 2);
            $table->decimal('principal_paid', 12, 2)->default(0);
            $table->decimal('interest_paid', 12, 2)->default(0);
            $table->decimal('late_fee_paid', 12, 2)->default(0);
            $table->decimal('previous_balance', 12, 2);
            $table->decimal('new_balance', 12, 2);
            $table->enum('payment_method', ['cash', 'transfer', 'card', 'check', 'other'])->default('cash');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'payment_date']);
            $table->index(['account_payable_id', 'payment_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_payable_payments');
        Schema::dropIfExists('account_payable_installments');
        Schema::dropIfExists('accounts_payable');
        Schema::dropIfExists('creditors');
    }
};
