<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_periods', function (Blueprint $table): void {
            $table->id();

            $table->uuid('public_id')
                ->unique();

            $table->foreignId('tenant_id')
                ->constrained('tenants')
                ->cascadeOnDelete();

            $table->foreignId('fiscal_year_id')
                ->constrained('fiscal_years')
                ->cascadeOnDelete();

            $table->string('name', 100);

            $table->unsignedTinyInteger('period_number');

            $table->date('start_date');

            $table->date('end_date');

            $table->string('status', 20)
                ->default('open');

            $table->timestamp('closed_at')
                ->nullable();

            $table->foreignId('closed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->unique([
                'fiscal_year_id',
                'period_number',
            ]);

            $table->unique([
                'fiscal_year_id',
                'start_date',
                'end_date',
            ]);

            $table->index([
                'tenant_id',
                'fiscal_year_id',
            ]);

            $table->index([
                'tenant_id',
                'status',
            ]);

            $table->index([
                'tenant_id',
                'start_date',
                'end_date',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_periods');
    }
};
