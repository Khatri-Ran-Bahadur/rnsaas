<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounting_account_groups', function (Blueprint $table) {
            $table->foreignId('parent_id')
                ->nullable()
                ->after('id')
                ->constrained('accounting_account_groups')
                ->restrictOnDelete();

            $table->index([
                'tenant_id',
                'parent_id',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('accounting_account_groups', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropIndex([
                'tenant_id',
                'parent_id',
            ]);
            $table->dropColumn('parent_id');
        });
    }
};
