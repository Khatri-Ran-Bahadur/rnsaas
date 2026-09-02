<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_user', function (Blueprint $table) {
            $table->id();

            /*
             * Identity
             */
            $table->foreignId('tenant_id')
                ->constrained('tenants')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            /*
             * Membership lifecycle
             *
             * invited  -> invitation sent
             * active   -> user can access tenant
             * suspended -> temporarily blocked
             * revoked  -> membership permanently revoked
             */
            $table->string('status', 20)->default('invited');

            /*
             * Membership metadata
             */
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('invited_at')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->timestamp('revoked_at')->nullable();

            /*
             * Who performed important membership actions.
             *
             * Nullable because the original inviter/deactivator
             * may later be deleted or become unavailable.
             */
            $table->foreignId('invited_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('suspended_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('revoked_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            /*
             * Optional membership-level settings.
             *
             * Example:
             * - dashboard preferences
             * - notification preferences
             * - tenant-specific user settings
             */
            $table->json('settings')->nullable();

            /*
             * Optimistic locking / concurrency support.
             *
             * Useful later for preventing silent overwrites
             * when multiple admins modify the same membership.
             */
            $table->unsignedBigInteger('version')->default(1);

            $table->timestamps();

            /*
             * A user can belong to a tenant only once.
             */
            $table->unique(
                ['tenant_id', 'user_id'],
                'tenant_user_tenant_id_user_id_unique'
            );

            /*
             * Common query patterns.
             */
            $table->index(
                ['tenant_id', 'status'],
                'tenant_user_tenant_status_index'
            );

            $table->index(
                ['user_id', 'status'],
                'tenant_user_user_status_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_user');
    }
};
