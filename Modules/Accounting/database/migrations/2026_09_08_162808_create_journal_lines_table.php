<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_lines', function (Blueprint $table): void {
            $table->id();

            $table->uuid('public_id')
                ->unique();

            $table->foreignId('tenant_id')
                ->constrained('tenants')
                ->cascadeOnDelete();

            $table->foreignId('journal_entry_id')
                ->constrained('journal_entries')
                ->cascadeOnDelete();

            $table->foreignId('account_id')
                ->constrained('accounting_accounts')
                ->restrictOnDelete();

            $table->unsignedInteger('line_number');

            $table->string('line_type', 10);

            /*
             * Stored as DECIMAL instead of floating point.
             *
             * 20,6 gives enough precision for multi-country
             * accounting and future exchange-rate calculations.
             */
            $table->decimal('amount', 20, 6);

            $table->string('description', 500)
                ->nullable();

            $table->timestamps();

            $table->unique([
                'journal_entry_id',
                'line_number',
            ]);

            $table->index([
                'tenant_id',
                'account_id',
                'journal_entry_id',
            ]);

            $table->index([
                'tenant_id',
                'account_id',
                'line_type',
            ]);

            $table->index([
                'tenant_id',
                'journal_entry_id',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_lines');
    }
};
