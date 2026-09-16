<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tax_settings', function (Blueprint $table): void {
            if (! Schema::hasColumn('tax_settings', 'output_tax_account_id')) {
                $table->foreignId('output_tax_account_id')->nullable()->after('default_purchase_tax_rate_id')->constrained('accounting_accounts')->nullOnDelete();
            }
            if (! Schema::hasColumn('tax_settings', 'input_tax_account_id')) {
                $table->foreignId('input_tax_account_id')->nullable()->after('output_tax_account_id')->constrained('accounting_accounts')->nullOnDelete();
            }
            if (! Schema::hasColumn('tax_settings', 'withholding_tax_account_id')) {
                $table->foreignId('withholding_tax_account_id')->nullable()->after('input_tax_account_id')->constrained('accounting_accounts')->nullOnDelete();
            }
            if (! Schema::hasColumn('tax_settings', 'tax_settlement_account_id')) {
                $table->foreignId('tax_settlement_account_id')->nullable()->after('withholding_tax_account_id')->constrained('accounting_accounts')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('tax_settings', function (Blueprint $table): void {
            $table->dropForeign(['output_tax_account_id']);
            $table->dropForeign(['input_tax_account_id']);
            $table->dropForeign(['withholding_tax_account_id']);
            $table->dropForeign(['tax_settlement_account_id']);
            $table->dropColumn([
                'output_tax_account_id',
                'input_tax_account_id',
                'withholding_tax_account_id',
                'tax_settlement_account_id',
            ]);
        });
    }
};
