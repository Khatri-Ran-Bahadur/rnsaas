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
        if (! Schema::hasTable('messages') || Schema::hasColumn('messages', 'reply_to_id')) {
            return;
        }

        Schema::table('messages', function (Blueprint $table) {
            $table->foreignId('reply_to_id')->nullable()->after('conversation_id')->constrained('messages')->nullOnDelete();
            $table->boolean('is_deleted')->default(false)->after('is_read');
            $table->timestamp('edited_at')->nullable()->after('is_deleted');
            $table->json('reactions')->nullable()->after('edited_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropForeign(['reply_to_id']);
            $table->dropColumn(['reply_to_id', 'is_deleted', 'edited_at', 'reactions']);
        });
    }
};
