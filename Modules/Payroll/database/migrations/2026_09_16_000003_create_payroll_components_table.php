<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_components', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();

            $table->string('name', 100);
            $table->string('code', 50);
            $table->string('type', 30)->default('earning'); // earning, deduction, benefit, employer_statutory
            $table->string('calculation_type', 30)->default('fixed'); // fixed, percentage_of_basic, hourly
            $table->decimal('default_amount', 15, 2)->default(0);
            $table->decimal('percentage_rate', 5, 2)->nullable(); // e.g. 10.00%
            $table->boolean('is_taxable')->default(true);
            $table->boolean('is_statutory')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);

            $table->timestamps();

            $table->unique(['tenant_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_components');
    }
};
