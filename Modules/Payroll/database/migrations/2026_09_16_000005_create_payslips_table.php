<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payslips', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('payroll_run_id')->constrained('payroll_runs')->cascadeOnDelete();
            $table->foreignId('staff_id')->constrained('tenant_staff')->cascadeOnDelete();

            $table->string('payslip_number', 50);
            $table->date('period_start');
            $table->date('period_end');
            $table->date('pay_date');

            // Salary components summary
            $table->decimal('base_salary', 15, 2)->default(0);
            $table->decimal('gross_earnings', 15, 2)->default(0);
            $table->decimal('total_deductions', 15, 2)->default(0);
            $table->decimal('net_payable', 15, 2)->default(0);

            // Detailed breakdowns
            $table->decimal('tax_deduction', 15, 2)->default(0); // TDS
            $table->decimal('ssf_employee_deduction', 15, 2)->default(0); // 11% SSF
            $table->decimal('ssf_employer_contribution', 15, 2)->default(0); // 20% SSF
            $table->decimal('pf_employee_deduction', 15, 2)->default(0); // 10% PF
            $table->decimal('pf_employer_contribution', 15, 2)->default(0); // 10% PF
            $table->decimal('loan_deductions', 15, 2)->default(0);
            $table->decimal('lop_deduction', 15, 2)->default(0); // Loss of pay for absent days
            $table->decimal('overtime_earnings', 15, 2)->default(0);

            // Attendance metrics
            $table->integer('working_days')->default(30);
            $table->decimal('present_days', 5, 2)->default(30);
            $table->decimal('absent_days', 5, 2)->default(0);
            $table->decimal('leave_days', 5, 2)->default(0);
            $table->decimal('overtime_hours', 5, 2)->default(0);

            $table->string('payment_status', 30)->default('unpaid'); // unpaid, paid
            $table->timestamp('paid_at')->nullable();
            $table->string('payment_method', 50)->nullable();
            $table->string('payment_reference', 100)->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->unique(['tenant_id', 'payroll_run_id', 'staff_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payslips');
    }
};
