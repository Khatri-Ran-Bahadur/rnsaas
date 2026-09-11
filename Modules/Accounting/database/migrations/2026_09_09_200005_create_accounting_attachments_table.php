<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_attachments', function (Blueprint $table) {
            $table->id();

            $table->uuid('public_id')->unique();

            $table->foreignId('tenant_id')
                ->constrained('tenants')
                ->cascadeOnDelete();

            $table->nullableMorphs('attachable');

            $table->string('disk', 50)->default('private');

            $table->string('path');

            $table->string('original_name');

            $table->string('mime_type', 150);

            $table->unsignedBigInteger('size');

            $table->foreignId('uploaded_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index([
                'tenant_id',
                'attachable_type',
                'attachable_id',
            ], 'acct_attachments_attachable_idx');

            $table->index([
                'tenant_id',
                'uploaded_by',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_attachments');
    }
};
