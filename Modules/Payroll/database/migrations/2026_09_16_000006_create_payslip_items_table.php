<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payslip_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('payslip_id')->constrained('payslips')->cascadeOnDelete();
            $table->foreignId('payroll_component_id')->nullable()->constrained('payroll_components')->nullOnDelete();

            $table->string('name', 100);
            $table->string('code', 50)->nullable();
            $table->string('type', 30); // earning, deduction, benefit, employer_statutory
            $table->decimal('amount', 15, 2)->default(0);
            $table->boolean('is_taxable')->default(true);
            $table->json('calculation_details')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payslip_items');
    }
};
