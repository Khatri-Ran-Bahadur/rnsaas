<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_entries', function (Blueprint $table): void {
            $table->id();

            $table->uuid('public_id')
                ->unique();

            $table->foreignId('tenant_id')
                ->constrained('tenants')
                ->cascadeOnDelete();

            $table->foreignId('fiscal_year_id')
                ->constrained('fiscal_years')
                ->restrictOnDelete();

            $table->foreignId('accounting_period_id')
                ->constrained('accounting_periods')
                ->restrictOnDelete();

            $table->string('entry_number', 50);

            $table->date('entry_date');

            $table->string('reference_type', 100)
                ->nullable();

            $table->string('reference_id', 100)
                ->nullable();

            $table->string('description', 500);

            $table->string('status', 20)
                ->default('draft');

            /*
             * Used to prevent the same external transaction
             * from being posted more than once.
             */
            $table->string('idempotency_key', 150)
                ->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('posted_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('posted_at')
                ->nullable();

            $table->foreignId('reversed_by')
                ->nullable()
                ->constrained('journal_entries')
                ->nullOnDelete();

            $table->foreignId('reverses_journal_entry_id')
                ->nullable()
                ->constrained('journal_entries')
                ->nullOnDelete();

            $table->timestamps();

            $table->unique([
                'tenant_id',
                'entry_number',
            ]);

            $table->unique([
                'tenant_id',
                'idempotency_key',
            ]);

            $table->index([
                'tenant_id',
                'entry_date',
            ]);

            $table->index([
                'tenant_id',
                'status',
            ]);

            $table->index([
                'tenant_id',
                'fiscal_year_id',
                'accounting_period_id',
            ], 'journal_entries_tenant_fy_period_index');

            $table->index([
                'tenant_id',
                'reference_type',
                'reference_id',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_entries');
    }
};
