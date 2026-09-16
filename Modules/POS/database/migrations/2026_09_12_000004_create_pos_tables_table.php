<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_tables', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();

            $table->string('table_number', 50);
            $table->string('name', 100);
            $table->string('section', 50)->default('main'); // indoor, outdoor, terrace, bar, vip
            $table->integer('capacity')->default(4);
            $table->string('shape', 20)->default('square'); // square, round, rectangle
            $table->string('status', 30)->default('available'); // available, occupied, reserved, billing, cleaning

            $table->integer('x_pos')->default(0);
            $table->integer('y_pos')->default(0);
            $table->integer('width')->default(100);
            $table->integer('height')->default(100);

            $table->string('current_order_ref')->nullable();
            $table->decimal('current_order_total', 15, 2)->default(0);
            $table->integer('occupied_minutes')->default(0);
            $table->string('assigned_server')->nullable();

            $table->timestamps();

            $table->unique(['tenant_id', 'table_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_tables');
    }
};
