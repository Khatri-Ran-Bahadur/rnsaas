<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounting_accounts', function (Blueprint $table) {
            $table->string('financial_statement_section')
                ->nullable()
                ->after('account_type_id');

            $table->index([
                'tenant_id',
                'financial_statement_section',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('accounting_accounts', function (Blueprint $table) {
            $table->dropIndex([
                'tenant_id',
                'financial_statement_section',
            ]);

            $table->dropColumn('financial_statement_section');
        });
    }
};
