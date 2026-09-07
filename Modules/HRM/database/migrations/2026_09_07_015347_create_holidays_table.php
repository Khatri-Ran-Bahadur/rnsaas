<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('holidays', function (Blueprint $table) {
            $table->id();

            $table->uuid('public_id')->unique();

            $table->foreignId('tenant_id')
                ->constrained('tenants')
                ->cascadeOnDelete();

            $table->string('name', 150);

            $table->date('start_date');

            $table->date('end_date')->nullable();

            $table->string('type', 30);

            $table->string('description', 500)->nullable();

            $table->boolean('is_recurring')->default(false);

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(
                ['tenant_id', 'name', 'start_date'],
                'holidays_tenant_name_start_date_unique'
            );

            $table->index(
                ['tenant_id', 'start_date', 'end_date'],
                'holidays_tenant_date_range_index'
            );

            $table->index(
                ['tenant_id', 'is_active'],
                'holidays_tenant_active_index'
            );

            $table->index(
                ['tenant_id', 'type'],
                'holidays_tenant_type_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('holidays');
    }
};
