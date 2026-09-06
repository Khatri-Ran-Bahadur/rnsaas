<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')
                ->constrained('tenants')
                ->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('slug', 100);
            $table->string('description', 255)->nullable();
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'slug'], 'tenant_roles_tenant_slug_unique');
            $table->index(['tenant_id', 'is_active'], 'tenant_roles_tenant_active_index');
        });

        Schema::create('tenant_role_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_role_id')
                ->constrained('tenant_roles')
                ->cascadeOnDelete();
            $table->string('permission', 100);
            $table->timestamps();

            $table->unique(['tenant_role_id', 'permission'], 'tenant_role_permissions_role_perm_unique');
            $table->index('permission', 'tenant_role_permissions_permission_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_role_permissions');
        Schema::dropIfExists('tenant_roles');
    }
};
