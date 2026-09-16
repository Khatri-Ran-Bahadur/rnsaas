<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_kitchen_tickets', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('pos_orders')->nullOnDelete();

            $table->string('ticket_number', 50);
            $table->string('order_ref', 50)->nullable();
            $table->string('order_type', 30)->default('dine_in');
            $table->string('destination', 100)->default('Counter');
            $table->string('station', 50)->default('kitchen'); // kitchen, bar, grill, bakery
            $table->string('priority', 20)->default('normal'); // normal, rush, vip
            $table->string('status', 30)->default('new'); // new, preparing, ready, served, cancelled

            $table->integer('elapsed_seconds')->default(0);
            $table->string('server_name')->nullable();
            $table->text('notes')->nullable();

            $table->timestamp('prepared_at')->nullable();
            $table->timestamp('served_at')->nullable();

            $table->timestamps();

            $table->unique(['tenant_id', 'ticket_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_kitchen_tickets');
    }
};
