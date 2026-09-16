<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mrp_material_issues', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('work_order_id')->nullable()->constrained('mrp_work_orders')->nullOnDelete();
            $table->string('issue_number')->index();
            $table->string('item_name');
            $table->string('item_sku')->index();
            $table->decimal('quantity', 10, 2);
            $table->string('unit')->default('kg');
            $table->string('issued_to')->nullable();
            $table->string('status')->default('issued');
            $table->dateTime('issued_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mrp_material_issues');
    }
};
