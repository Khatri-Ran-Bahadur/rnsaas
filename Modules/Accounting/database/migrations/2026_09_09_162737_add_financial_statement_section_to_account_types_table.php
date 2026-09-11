<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounting_account_types', function (Blueprint $table) {
            $table->string('financial_statement_section')
                ->nullable()
                ->after('normal_balance');

            $table->index([
                'tenant_id',
                'financial_statement_section',
            ], 'acct_types_tenant_sec_idx');
        });
    }

    public function down(): void
    {
        Schema::table('accounting_account_types', function (Blueprint $table) {
            $table->dropIndex('acct_types_tenant_sec_idx');

            $table->dropColumn('financial_statement_section');
        });
    }
};
