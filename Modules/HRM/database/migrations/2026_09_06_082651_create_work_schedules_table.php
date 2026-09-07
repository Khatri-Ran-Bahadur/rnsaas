<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_schedules', function (Blueprint $table) {
            $table->id();

            $table->uuid('public_id')
                ->unique();

            $table->foreignId('tenant_id')
                ->constrained('tenants')
                ->cascadeOnDelete();

            $table->string('name', 100);

            $table->string('description', 255)
                ->nullable();

            $table->string('timezone', 64);

            $table->time('default_start_time');

            $table->time('default_end_time');

            $table->unsignedSmallInteger('break_minutes')
                ->default(0);

            $table->unsignedSmallInteger('late_grace_minutes')
                ->default(0);

            $table->unsignedSmallInteger('early_leave_grace_minutes')
                ->default(0);

            $table->boolean('is_active')
                ->default(true);

            $table->timestamps();

            $table->unique(
                ['tenant_id', 'name'],
                'work_schedules_tenant_name_unique'
            );

            $table->index(
                ['tenant_id', 'is_active'],
                'work_schedules_tenant_active_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_schedules');
    }
};
