<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('overtimes', function (Blueprint $table) {
            $table->id();

            $table->uuid('public_id')->unique();

            $table->foreignId('tenant_id')
                ->constrained('tenants')
                ->cascadeOnDelete();

            $table->foreignId('tenant_staff_id')
                ->constrained('tenant_staff')
                ->restrictOnDelete();

            $table->date('date');

            $table->time('start_time');

            $table->time('end_time');

            $table->unsignedInteger('total_minutes');

            $table->string('type', 30);

            $table->string('status', 30)->default('pending');

            $table->decimal('rate_multiplier', 5, 2)->default(1.00);

            $table->string('reason', 500)->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('approved_at')->nullable();

            $table->foreignId('rejected_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('rejected_at')->nullable();

            $table->string('rejection_reason', 500)->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(
                ['tenant_id', 'date'],
                'overtimes_tenant_date_index'
            );

            $table->index(
                ['tenant_id', 'tenant_staff_id', 'date'],
                'overtimes_tenant_staff_date_index'
            );

            $table->index(
                ['tenant_id', 'status'],
                'overtimes_tenant_status_index'
            );

            $table->index(
                ['tenant_id', 'is_active'],
                'overtimes_tenant_active_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('overtimes');
    }
};
