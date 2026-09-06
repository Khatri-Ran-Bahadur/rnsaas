<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenant_user', function (Blueprint $table) {
            $table->foreignId('role_id')
                ->nullable()
                ->after('user_id')
                ->constrained('tenant_roles')
                ->nullOnDelete();

            $table->string('invitation_token', 64)
                ->nullable()
                ->unique()
                ->after('status');

            $table->timestamp('expires_at')
                ->nullable()
                ->after('invited_at');

            $table->index(['tenant_id', 'role_id'], 'tenant_user_tenant_role_index');
        });
    }

    public function down(): void
    {
        Schema::table('tenant_user', function (Blueprint $table) {
            $table->dropIndex('tenant_user_tenant_role_index');
            $table->dropConstrainedForeignId('role_id');
            $table->dropColumn(['invitation_token', 'expires_at']);
        });
    }
};
