<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_items', function (Blueprint $table): void {
            if (! Schema::hasColumn('inventory_items', 'branch_id')) {
                $table->foreignId('branch_id')->nullable()->after('tenant_id')->constrained('branches')->nullOnDelete();
            }

            if (! Schema::hasColumn('inventory_items', 'brand_name')) {
                $table->string('brand_name')->nullable()->after('name')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('inventory_items', function (Blueprint $table): void {
            if (Schema::hasColumn('inventory_items', 'branch_id')) {
                $table->dropConstrainedForeignId('branch_id');
            }

            if (Schema::hasColumn('inventory_items', 'brand_name')) {
                $table->dropColumn('brand_name');
            }
        });
    }
};
