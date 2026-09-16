<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_runs', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('payroll_group_id')->nullable()->constrained('payroll_groups')->nullOnDelete();

            $table->string('run_number', 50);
            $table->string('period_name', 100);
            $table->date('start_date');
            $table->date('end_date');
            $table->date('pay_date');
            $table->string('status', 30)->default('draft');

            $table->integer('total_employees')->default(0);
            $table->decimal('gross_amount', 15, 2)->default(0);
            $table->decimal('deductions_amount', 15, 2)->default(0);
            $table->decimal('net_amount', 15, 2)->default(0);
            $table->decimal('employer_statutory', 15, 2)->default(0);

            $table->string('created_by')->nullable();
            $table->string('approved_by')->nullable();

            $table->timestamps();

            $table->unique(['tenant_id', 'run_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_runs');
    }
};
