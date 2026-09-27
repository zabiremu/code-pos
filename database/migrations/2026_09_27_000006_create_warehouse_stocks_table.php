<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Stock of each product per warehouse. products.stock_quantity stays as
     * the total across all warehouses (what low-stock checks and the POS
     * read); App\Services\StockService keeps the two in step.
     */
    public function up(): void
    {
        Schema::create('warehouse_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 14, 3)->default(0);
            $table->timestamps();

            $table->unique(['warehouse_id', 'product_id']);
        });

        // Existing installs: whatever stock products already have now lives
        // in the default warehouse, so the per-warehouse totals match.
        $warehouseId = DB::table('warehouses')->where('is_default', true)->value('id');
        if ($warehouseId) {
            DB::table('products')->where('stock_quantity', '!=', 0)->orderBy('id')
                ->each(function ($product) use ($warehouseId) {
                    DB::table('warehouse_stocks')->insert([
                        'warehouse_id' => $warehouseId,
                        'product_id' => $product->id,
                        'quantity' => $product->stock_quantity,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_stocks');
    }
};
