<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('code', 50)->nullable();
            $table->string('full_name', 180);
            $table->string('identification', 50)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('secondary_phone', 50)->nullable();
            $table->string('email', 150)->nullable();
            $table->text('address')->nullable();
            $table->string('workplace', 180)->nullable();
            $table->string('workplace_phone', 50)->nullable();
            $table->decimal('monthly_income', 12, 2)->default(0);
            $table->string('photo')->nullable();
            $table->enum('status', ['active', 'inactive', 'moroso', 'blocked'])->default('active');
            $table->enum('risk_level', ['low', 'medium', 'high', 'critical'])->default('low');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'code']);
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'identification']);
        });

        Schema::create('client_references', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('phone', 50);
            $table->string('relationship', 100)->nullable();
            $table->text('address')->nullable();
            $table->timestamps();
        });

        Schema::create('client_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('document_type', 100);
            $table->string('title', 180);
            $table->string('file_path');
            $table->timestamps();
        });

        Schema::create('collectors', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 150);
            $table->string('phone', 50)->nullable();
            $table->enum('commission_type', ['percentage', 'fixed', 'none'])->default('none');
            $table->decimal('commission_value', 12, 2)->default(0);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();

            $table->index(['company_id', 'status']);
        });

        Schema::create('zones', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'name']);
        });

        Schema::create('routes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('zone_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('collector_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();

            $table->unique(['company_id', 'name']);
            $table->index(['company_id', 'status']);
        });

        Schema::create('route_clients', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('route_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('order_number')->default(0);
            $table->timestamps();

            $table->unique(['route_id', 'client_id']);
        });

        Schema::create('loan_quotes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 12, 2);
            $table->decimal('interest_rate', 8, 4);
            $table->enum('interest_type', ['fixed', 'compound', 'amortized'])->default('fixed');
            $table->enum('payment_frequency', ['daily', 'weekly', 'biweekly', 'monthly']);
            $table->enum('calculation_method', ['flat_interest', 'fixed_installment', 'capital_plus_interest', 'interest_only', 'french_amortization']);
            $table->unsignedInteger('term_quantity');
            $table->decimal('installment_amount', 12, 2);
            $table->decimal('total_interest', 12, 2);
            $table->decimal('total_to_pay', 12, 2);
            $table->date('start_date')->nullable();
            $table->date('first_payment_date')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'converted'])->default('pending');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'status']);
        });

        Schema::create('loans', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->restrictOnDelete();
            $table->foreignId('collector_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('quote_id')->nullable()->constrained('loan_quotes')->nullOnDelete();
            $table->string('loan_number', 50);
            $table->decimal('principal_amount', 12, 2);
            $table->decimal('interest_rate', 8, 4);
            $table->enum('interest_type', ['fixed', 'compound', 'amortized'])->default('fixed');
            $table->enum('payment_frequency', ['daily', 'weekly', 'biweekly', 'monthly']);
            $table->enum('calculation_method', ['flat_interest', 'fixed_installment', 'capital_plus_interest', 'interest_only', 'french_amortization']);
            $table->unsignedInteger('term_quantity');
            $table->decimal('installment_amount', 12, 2);
            $table->decimal('total_interest', 12, 2);
            $table->decimal('total_amount', 12, 2);
            $table->decimal('paid_principal', 12, 2)->default(0);
            $table->decimal('paid_interest', 12, 2)->default(0);
            $table->decimal('paid_late_fee', 12, 2)->default(0);
            $table->decimal('remaining_balance', 12, 2);
            $table->enum('late_fee_type', ['none', 'fixed', 'daily_percentage', 'daily_fixed'])->default('none');
            $table->decimal('late_fee_value', 12, 2)->default(0);
            $table->date('start_date');
            $table->date('first_payment_date');
            $table->date('end_date')->nullable();
            $table->enum('status', ['active', 'late', 'paid', 'refinanced', 'cancelled', 'legal', 'written_off'])->default('active');
            $table->text('guarantee_description')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'loan_number']);
            $table->index(['company_id', 'status']);
            $table->index(['client_id', 'status']);
        });

        Schema::create('loan_installments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('loan_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('installment_number');
            $table->date('due_date');
            $table->decimal('principal_amount', 12, 2)->default(0);
            $table->decimal('interest_amount', 12, 2)->default(0);
            $table->decimal('installment_amount', 12, 2);
            $table->decimal('paid_principal', 12, 2)->default(0);
            $table->decimal('paid_interest', 12, 2)->default(0);
            $table->decimal('paid_late_fee', 12, 2)->default(0);
            $table->decimal('total_paid', 12, 2)->default(0);
            $table->decimal('late_fee', 12, 2)->default(0);
            $table->unsignedInteger('days_late')->default(0);
            $table->enum('status', ['pending', 'partial', 'paid', 'late', 'cancelled'])->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->unique(['loan_id', 'installment_number']);
            $table->index(['loan_id', 'status']);
            $table->index('due_date');
        });

        Schema::create('payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('loan_id')->constrained()->restrictOnDelete();
            $table->foreignId('client_id')->constrained()->restrictOnDelete();
            $table->foreignId('collector_id')->nullable()->constrained()->nullOnDelete();
            $table->string('receipt_number', 50);
            $table->date('payment_date');
            $table->decimal('amount', 12, 2);
            $table->decimal('principal_paid', 12, 2)->default(0);
            $table->decimal('interest_paid', 12, 2)->default(0);
            $table->decimal('late_fee_paid', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->enum('payment_method', ['cash', 'transfer', 'card', 'check', 'other'])->default('cash');
            $table->decimal('previous_balance', 12, 2);
            $table->decimal('new_balance', 12, 2);
            $table->enum('status', ['valid', 'cancelled'])->default('valid');
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'receipt_number']);
            $table->index(['company_id', 'payment_date']);
            $table->index(['loan_id', 'status']);
        });

        Schema::create('payment_details', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('installment_id')->constrained('loan_installments')->restrictOnDelete();
            $table->decimal('principal_paid', 12, 2)->default(0);
            $table->decimal('interest_paid', 12, 2)->default(0);
            $table->decimal('late_fee_paid', 12, 2)->default(0);
            $table->decimal('amount_paid', 12, 2);
            $table->timestamps();
        });

        Schema::create('collector_commissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('collector_id')->constrained()->restrictOnDelete();
            $table->foreignId('payment_id')->constrained()->restrictOnDelete();
            $table->enum('commission_type', ['percentage', 'fixed']);
            $table->decimal('commission_value', 12, 2);
            $table->decimal('base_amount', 12, 2);
            $table->decimal('commission_amount', 12, 2);
            $table->enum('status', ['pending', 'paid', 'cancelled'])->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status']);
        });

        Schema::create('cash_movements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['loan_disbursement', 'payment_received', 'expense', 'collector_commission', 'capital_injection', 'capital_withdrawal', 'adjustment']);
            $table->decimal('amount', 12, 2);
            $table->enum('direction', ['in', 'out']);
            $table->string('reference_type', 100)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('description')->nullable();
            $table->date('movement_date');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'movement_date']);
            $table->index(['reference_type', 'reference_id']);
        });

        Schema::create('expense_categories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name', 150);
            $table->timestamps();

            $table->unique(['company_id', 'name']);
        });

        Schema::create('expenses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('expense_categories')->nullOnDelete();
            $table->text('description');
            $table->decimal('amount', 12, 2);
            $table->date('expense_date');
            $table->enum('payment_method', ['cash', 'transfer', 'card', 'check', 'other'])->default('cash');
            $table->string('receipt_file')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'expense_date']);
        });

        Schema::create('documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('loan_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('document_type', ['promissory_note', 'loan_contract', 'disbursement_receipt', 'payment_receipt', 'balance_letter', 'account_statement']);
            $table->string('title', 180);
            $table->string('file_path');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'document_type']);
        });

        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 150);
            $table->string('module', 100);
            $table->text('description')->nullable();
            $table->string('auditable_type', 150)->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 50)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['company_id', 'module']);
            $table->index(['auditable_type', 'auditable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('documents');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('expense_categories');
        Schema::dropIfExists('cash_movements');
        Schema::dropIfExists('collector_commissions');
        Schema::dropIfExists('payment_details');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('loan_installments');
        Schema::dropIfExists('loans');
        Schema::dropIfExists('loan_quotes');
        Schema::dropIfExists('route_clients');
        Schema::dropIfExists('routes');
        Schema::dropIfExists('zones');
        Schema::dropIfExists('collectors');
        Schema::dropIfExists('client_documents');
        Schema::dropIfExists('client_references');
        Schema::dropIfExists('clients');
    }
};
