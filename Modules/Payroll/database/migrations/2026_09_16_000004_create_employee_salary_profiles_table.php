<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_salary_profiles', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('staff_id')->constrained('tenant_staff')->cascadeOnDelete();
            $table->foreignId('payroll_group_id')->nullable()->constrained('payroll_groups')->nullOnDelete();

            $table->decimal('base_salary', 15, 2)->default(0);
            $table->decimal('hourly_rate', 10, 2)->default(0);
            $table->string('wage_type', 30)->default('monthly'); // monthly, hourly, daily, contract
            $table->string('payment_method', 50)->default('bank_transfer'); // bank_transfer, cash, cheque

            $table->string('bank_name', 100)->nullable();
            $table->string('bank_account_number', 100)->nullable();
            $table->string('bank_account_name', 150)->nullable();
            $table->string('tax_status', 30)->default('single'); // single, married
            $table->string('pan_number', 50)->nullable();
            $table->string('ssf_number', 50)->nullable();
            $table->string('cit_number', 50)->nullable();

            $table->json('custom_components')->nullable(); // array of customized allowances / deductions
            $table->boolean('is_active')->default(true);
            $table->date('effective_from')->nullable();

            $table->timestamps();

            $table->unique(['tenant_id', 'staff_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_salary_profiles');
    }
};
