<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();

            /*
             * Public identifier.
             *
             * Internal numeric ID is useful for database joins.
             * Public ID prevents exposing sequential IDs externally.
             */
            $table->ulid('public_id')->unique();

            /*
             * Tenant boundary.
             *
             * Platform-level actions may have no tenant.
             */
            $table->foreignId('tenant_id')
                ->nullable()
                ->constrained('tenants')
                ->nullOnDelete();

            /*
             * Actor who performed the action.
             *
             * Polymorphic because later an action could be performed
             * by different authenticatable models.
             */
            $table->nullableMorphs('actor');

            /*
             * Business event name.
             *
             * Examples:
             * tenant.created
             * membership.invited
             * membership.suspended
             * invoice.paid
             */
            $table->string('event', 150);

            /*
             * Resource affected by the action.
             *
             * Example:
             * TenantMembership #25
             * Invoice #1002
             * Product #55
             */
            $table->nullableMorphs('auditable');

            /*
             * State before and after the operation.
             *
             * Never store secrets, passwords, tokens or API keys here.
             */
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();

            /*
             * Additional contextual information.
             *
             * Examples:
             * module
             * source
             * reason
             * import batch
             * API version
             */
            $table->json('metadata')->nullable();

            /*
             * Correlates all logs generated during one request/job.
             */
            $table->string('request_id', 100)->nullable();

            /*
             * Security/request context.
             */
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();

            /*
             * Audit records are immutable.
             * No updated_at.
             */
            $table->timestamp('created_at');

            /*
             * Query optimization.
             */
            $table->index(
                ['tenant_id', 'created_at'],
                'audit_logs_tenant_created_index'
            );

            $table->index(
                ['event', 'created_at'],
                'audit_logs_event_created_index'
            );

            $table->index(
                ['request_id'],
                'audit_logs_request_id_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
