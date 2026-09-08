<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_accounts', function (Blueprint $table): void {
            $table->id();

            $table->uuid('public_id')->unique();

            $table->foreignId('tenant_id')
                ->constrained('tenants')
                ->cascadeOnDelete();

            $table->foreignId('account_type_id')
                ->constrained('accounting_account_types')
                ->restrictOnDelete();

            $table->foreignId('account_group_id')
                ->constrained('accounting_account_groups')
                ->restrictOnDelete();

            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('accounting_accounts')
                ->restrictOnDelete();

            $table->string('code', 50);
            $table->string('name', 150);

            $table->text('description')->nullable();

            /*
             * Header account:
             * cannot receive journal postings.
             *
             * Posting account:
             * can receive journal postings.
             */
            $table->boolean('is_postable')->default(true);

            /*
             * Control account:
             * normally maintained by another subledger/module.
             *
             * Examples:
             * Accounts Receivable
             * Accounts Payable
             * Inventory
             */
            $table->boolean('is_control_account')->default(false);

            /*
             * System accounts are provisioned by SathiSaaS.
             * They cannot be hard deleted.
             */
            $table->boolean('is_system')->default(false);

            $table->boolean('is_active')->default(true);

            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->unique([
                'tenant_id',
                'code',
            ]);

            $table->index([
                'tenant_id',
                'parent_id',
            ]);

            $table->index([
                'tenant_id',
                'account_type_id',
            ]);

            $table->index([
                'tenant_id',
                'account_group_id',
            ]);

            $table->index([
                'tenant_id',
                'is_active',
            ]);

            $table->index([
                'tenant_id',
                'is_postable',
            ]);

            $table->index([
                'tenant_id',
                'is_control_account',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_accounts');
    }
};
