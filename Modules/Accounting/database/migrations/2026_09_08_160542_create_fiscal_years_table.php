<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fiscal_years', function (Blueprint $table): void {
            $table->id();

            $table->uuid('public_id')
                ->unique();

            $table->foreignId('tenant_id')
                ->constrained('tenants')
                ->cascadeOnDelete();

            $table->string('code', 50);

            $table->string('name', 150);

            $table->date('start_date');

            $table->date('end_date');

            $table->string('status', 20)
                ->default('open');

            $table->boolean('is_current')
                ->default(false);

            $table->timestamp('closed_at')
                ->nullable();

            $table->foreignId('closed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->unique([
                'tenant_id',
                'code',
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

            $table->index([
                'tenant_id',
                'is_current',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fiscal_years');
    }
};
