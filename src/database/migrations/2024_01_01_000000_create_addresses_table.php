<?php

declare(strict_types=1);

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
        Schema::create('addresses', function (Blueprint $table) {
            // Primary key with UUID support
            $table->uuid('id')->primary();

            // Polymorphic relationship
            $table->string('addressable_type');
            $table->foreignId('addressable_id');

            // Address type and label
            $table->string('type')->default('general'); // home, work, billing, shipping, etc.
            $table->string('label')->nullable();

            // Personal information
            $table->string('given_name');
            $table->string('family_name')->nullable();
            $table->string('organization')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();

            // Address components
            $table->string('street')->nullable();
            $table->string('street_2')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country_code', 2)->nullable();
            $table->string('country_name')->nullable();
            $table->string('neighborhood')->nullable();
            $table->string('district')->nullable();

            // Coordinates with spatial support
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();

            // Spatial index for location-based queries
            if (config('database.default') === 'mysql') {
                $table->spatialIndex(['latitude', 'longitude'], 'addresses_location_spatial');
            }

            // Address flags
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_billing')->default(false);
            $table->boolean('is_shipping')->default(false);
            $table->boolean('is_verified')->default(false);

            // Metadata
            $table->json('metadata')->nullable(); // For additional country-specific fields
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index(['addressable_type', 'addressable_id'], 'addresses_addressable_index');
            $table->index(['type'], 'addresses_type_index');
            $table->index(['country_code'], 'addresses_country_index');
            $table->index(['is_primary'], 'addresses_primary_index');
            $table->index(['is_billing'], 'addresses_billing_index');
            $table->index(['is_shipping'], 'addresses_shipping_index');
            $table->index(['created_at'], 'addresses_created_at_index');

            // Composite indexes for common queries
            $table->index(['addressable_type', 'addressable_id', 'type'], 'addresses_polymorphic_type_index');
            $table->index(['addressable_type', 'addressable_id', 'is_primary'], 'addresses_polymorphic_primary_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};
