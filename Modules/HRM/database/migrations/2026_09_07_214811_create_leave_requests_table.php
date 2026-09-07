<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_requests', function (Blueprint $table): void {
            $table->id();

            $table->uuid('public_id')->unique();

            $table->foreignId('tenant_id')
                ->constrained('tenants')
                ->cascadeOnDelete();

            $table->foreignId('tenant_staff_id')
                ->constrained('tenant_staff')
                ->restrictOnDelete();

            $table->string('leave_type', 40);

            $table->date('start_date');
            $table->date('end_date');

            /*
             * Decimal allows future half-day support.
             *
             * Examples:
             * 1.00 = one full day
             * 0.50 = half day
             * 2.50 = two and a half days
             */
            $table->decimal('total_days', 5, 2);

            $table->text('reason')->nullable();

            $table->string('status', 30)->default('pending');

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

            $table->text('rejection_reason')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index([
                'tenant_id',
                'start_date',
                'end_date',
            ]);

            $table->index([
                'tenant_id',
                'tenant_staff_id',
                'start_date',
            ]);

            $table->index([
                'tenant_id',
                'status',
            ]);

            $table->index([
                'tenant_id',
                'leave_type',
            ]);

            $table->index([
                'tenant_id',
                'is_active',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
    }
};
