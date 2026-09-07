<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table): void {
            $table->id();

            $table->uuid('public_id')->unique();

            $table->foreignId('tenant_id')
                ->constrained('tenants')
                ->cascadeOnDelete();

            $table->foreignId('tenant_staff_id')
                ->constrained('tenant_staff')
                ->restrictOnDelete();

            $table->date('attendance_date');

            $table->time('check_in')->nullable();
            $table->time('check_out')->nullable();

            $table->unsignedInteger('worked_minutes')->default(0);
            $table->unsignedInteger('late_minutes')->default(0);
            $table->unsignedInteger('early_leave_minutes')->default(0);
            $table->unsignedInteger('overtime_minutes')->default(0);

            $table->string('status', 30);

            $table->string('source', 30)->default('manual');

            $table->text('notes')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            /*
             * One attendance record per employee per date.
             */
            $table->unique([
                'tenant_id',
                'tenant_staff_id',
                'attendance_date',
            ]);

            $table->index([
                'tenant_id',
                'attendance_date',
            ]);

            $table->index([
                'tenant_id',
                'tenant_staff_id',
                'attendance_date',
            ]);

            $table->index([
                'tenant_id',
                'status',
            ]);

            $table->index([
                'tenant_id',
                'source',
            ]);

            $table->index([
                'tenant_id',
                'is_active',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
