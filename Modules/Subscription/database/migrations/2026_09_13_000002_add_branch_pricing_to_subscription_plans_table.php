<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            if (! Schema::hasColumn('subscription_plans', 'included_branches')) {
                $table->unsignedInteger('included_branches')->default(1)->after('trial_days');
            }
            if (! Schema::hasColumn('subscription_plans', 'extra_branch_price')) {
                $table->decimal('extra_branch_price', 10, 2)->default(15.00)->after('included_branches');
            }
        });

        Schema::table('tenant_subscriptions', function (Blueprint $table) {
            if (! Schema::hasColumn('tenant_subscriptions', 'allowed_branches')) {
                $table->unsignedInteger('allowed_branches')->default(1)->after('status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->dropColumn(['included_branches', 'extra_branch_price']);
        });

        Schema::table('tenant_subscriptions', function (Blueprint $table) {
            $table->dropColumn(['allowed_branches']);
        });
    }
};
