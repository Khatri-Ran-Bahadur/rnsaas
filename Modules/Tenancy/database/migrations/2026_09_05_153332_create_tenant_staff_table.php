<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_staff', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();

            $table->foreignId('tenant_id')
                ->constrained('tenants')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('branch_id')
                ->constrained('branches')
                ->restrictOnDelete();

            $table->foreignId('department_id')
                ->constrained('departments')
                ->restrictOnDelete();

            $table->foreignId('designation_id')
                ->constrained('designations')
                ->restrictOnDelete();

            $table->string('employee_code', 50);

            $table->date('joining_date')->nullable();

            $table->string('employment_status', 20)
                ->default('active');

            $table->timestamp('suspended_at')->nullable();

            $table->timestamp('terminated_at')->nullable();

            $table->text('termination_reason')->nullable();

            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->unique(['tenant_id', 'user_id']);
            $table->unique(['tenant_id', 'employee_code']);

            $table->index(['tenant_id', 'employment_status']);
            $table->index(['tenant_id', 'branch_id']);
            $table->index(['tenant_id', 'department_id']);
            $table->index(['tenant_id', 'designation_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_staff');
    }
};
