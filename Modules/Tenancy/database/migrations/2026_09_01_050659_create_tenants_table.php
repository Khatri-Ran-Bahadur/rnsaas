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
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();

            // Public identifier. Internal numeric ID should not be exposed.
            $table->ulid('public_id')->unique();

            // Basic tenant identity.
            $table->string('name');
            $table->string('slug')->unique();

            // Business classification.
            $table->string('industry')->nullable();

            // Tenant lifecycle.
            $table->string('status')->default('pending');

            // Localization.
            $table->string('country_code', 2)->nullable();
            $table->string('timezone', 64)->default('UTC');
            $table->string('locale', 10)->default('en');
            $table->string('currency', 3)->default('USD');

            // Flexible tenant-level configuration.
            $table->json('settings')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Common filtering indexes.
            $table->index('status');
            $table->index('industry');
            $table->index('country_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
