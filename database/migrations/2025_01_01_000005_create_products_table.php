<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** A sellable product/item. Stock is tracked directly on the product (see track_stock/stock_quantity) rather than through a separate recipe/ingredient system. */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('sku')->nullable()->unique();
            $table->text('description')->nullable();
            $table->string('image_path')->nullable();
            $table->decimal('base_price', 10, 2);
            $table->decimal('tax_rate', 5, 2)->nullable(); // overrides branch default when set
            $table->boolean('track_stock')->default(false);
            $table->decimal('stock_quantity', 12, 3)->default(0);
            $table->decimal('low_stock_threshold', 12, 3)->default(0);
            $table->boolean('is_available')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
