<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('statutory_schemes', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();

            $table->string('country_code', 10)->default('NP'); // NP, MY, US, GB, IN, AE, etc.
            $table->string('name', 100);
            $table->string('scheme_type', 50)->default('income_tax'); // income_tax, social_security, provident_fund, gratuity
            $table->decimal('employee_rate', 5, 2)->default(0); // e.g. 11% SSF, 10% PF
            $table->decimal('employer_rate', 5, 2)->default(0); // e.g. 20% SSF, 10% PF

            $table->json('tax_slabs')->nullable(); // tiered brackets: [{'from': 0, 'to': 500000, 'rate': 1.0}, ...]
            $table->json('exemption_limits')->nullable(); // insurance, CIT, PF limits
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);

            $table->timestamps();

            $table->unique(['tenant_id', 'country_code', 'scheme_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('statutory_schemes');
    }
};
