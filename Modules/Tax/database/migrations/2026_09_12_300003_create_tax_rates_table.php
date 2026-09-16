<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_rates', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('tax_type_id')->nullable()->constrained('tax_types')->nullOnDelete();
            $table->string('name');
            $table->string('code')->index();
            $table->string('tax_category')->default('standard');
            $table->string('rate_type')->default('percentage'); // percentage, fixed
            $table->decimal('rate', 8, 4)->default(0.0000);
            $table->decimal('fixed_amount', 10, 2)->nullable();
            $table->date('effective_from')->nullable();
            $table->date('effective_until')->nullable();
            $table->string('timeline_status')->default('active'); // active, scheduled, expired, draft
            $table->string('country')->default('Global');
            $table->string('region')->default('All Regions');
            $table->boolean('is_recoverable')->default(true);
            $table->boolean('is_compound')->default(false);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'timeline_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_rates');
    }
};
