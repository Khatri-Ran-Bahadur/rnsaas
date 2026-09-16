<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_kitchen_ticket_items', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('ticket_id')->constrained('pos_kitchen_tickets')->cascadeOnDelete();

            $table->string('name');
            $table->integer('quantity')->default(1);
            $table->json('modifiers')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_done')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_kitchen_ticket_items');
    }
};
