<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 60);
            $table->string('slug', 70)->unique();
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_category_id')->constrained()->restrictOnDelete();
            $table->string('name', 120);
            $table->string('slug', 140)->unique();
            $table->text('description')->nullable();
            $table->unsignedInteger('price_cents');
            // Set = on sale: shown with the original price struck through.
            $table->unsignedInteger('sale_price_cents')->nullable();
            $table->json('tags')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'product_category_id']);
        });

        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->unsignedTinyInteger('position')->default(0);
            $table->timestamps();
        });

        // Sizes / colours. Every product has at least one ("One size"); stock is per variant.
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('name', 60);
            $table->unsignedInteger('stock')->default(0);
            $table->unsignedTinyInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->char('reference', 8)->unique();
            // Secret for the customer's order page link.
            $table->char('token', 64)->unique();
            $table->string('status', 20)->index();
            $table->foreignId('hiker_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 120);
            $table->string('phone', 20);
            $table->string('email', 190)->nullable();
            // pickup (from the organizers at a hike) | delivery
            $table->string('fulfilment', 20);
            $table->string('delivery_city', 80)->nullable();
            $table->string('delivery_address', 255)->nullable();
            $table->string('notes', 500)->nullable();
            $table->unsignedInteger('subtotal_cents');
            $table->unsignedInteger('delivery_fee_cents')->default(0);
            $table->unsignedInteger('total_cents');
            $table->string('payment_method', 20)->nullable();
            $table->string('payment_reference', 60)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason', 500)->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();
            // Copied at checkout so the order stays readable if the product changes.
            $table->string('product_name', 120);
            $table->string('variant_name', 60);
            $table->unsignedInteger('unit_price_cents');
            $table->unsignedSmallInteger('quantity');
            $table->timestamps();
        });

        // Online payments can now be for a booking or for a shop order.
        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('event_registration_id')->nullable()->change();
            $table->foreignId('order_id')->nullable()->after('event_registration_id')->constrained()->cascadeOnDelete();
        });

        foreach (['Accessories', 'Backpack', 'Clothing', 'Footwear', 'Jacket'] as $i => $name) {
            DB::table('product_categories')->insert([
                'name' => $name, 'slug' => strtolower($name), 'position' => $i, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('order_id');
        });
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('product_images');
        Schema::dropIfExists('products');
        Schema::dropIfExists('product_categories');
    }
};
