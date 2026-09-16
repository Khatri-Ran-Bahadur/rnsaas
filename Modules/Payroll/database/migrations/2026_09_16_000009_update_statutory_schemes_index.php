<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('statutory_schemes', function (Blueprint $table): void {
            $table->index('tenant_id');
            $table->dropUnique('statutory_schemes_tenant_id_country_code_scheme_type_unique');
            $table->string('code', 50)->nullable()->after('name');
            $table->unique(['tenant_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::table('statutory_schemes', function (Blueprint $table): void {
            $table->dropUnique(['tenant_id', 'name']);
            $table->dropColumn('code');
            $table->unique(['tenant_id', 'country_code', 'scheme_type']);
            $table->dropIndex(['tenant_id']);
        });
    }
};
