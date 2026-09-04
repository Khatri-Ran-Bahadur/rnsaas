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
        if (! Schema::hasTable('media_directories')) {
            Schema::create('media_directories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained('tenants')->cascadeOnDelete();
                $table->string('name');
                $table->string('slug');
                $table->foreignId('parent_id')->nullable()->constrained('media_directories')->cascadeOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['tenant_id', 'parent_id']);
            });
        }

        if (! Schema::hasTable('media')) {
            Schema::create('media', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained('tenants')->cascadeOnDelete();
                $table->string('name');
                $table->string('file_name');
                $table->string('mime_type')->nullable();
                $table->string('disk')->default('public');
                $table->unsignedBigInteger('size')->default(0);
                $table->foreignId('directory_id')->nullable()->constrained('media_directories')->nullOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['tenant_id', 'directory_id']);
                $table->index(['tenant_id', 'created_at']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media');
        Schema::dropIfExists('media_directories');
    }
};
