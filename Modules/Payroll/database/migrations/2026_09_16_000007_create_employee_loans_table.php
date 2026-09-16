<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_loans', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('staff_id')->constrained('tenant_staff')->cascadeOnDelete();

            $table->string('loan_number', 50);
            $table->string('loan_type', 50)->default('salary_advance'); // salary_advance, emergency_loan, company_loan
            $table->decimal('principal_amount', 15, 2);
            $table->decimal('interest_rate', 5, 2)->default(0); // e.g. 0% for advance, 5% for loan
            $table->decimal('total_repayable', 15, 2);
            $table->decimal('monthly_emi', 15, 2);
            $table->integer('tenure_months')->default(1);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->decimal('remaining_amount', 15, 2);

            $table->date('disbursed_at')->nullable();
            $table->date('start_deduction_date')->nullable();
            $table->string('status', 30)->default('active'); // active, paid_off, paused, cancelled
            $table->text('reason')->nullable();

            $table->timestamps();

            $table->unique(['tenant_id', 'loan_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_loans');
    }
};
