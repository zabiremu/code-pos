<?php

namespace App\Services;

use App\Models\Product;
use App\Models\SaleItem;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The one place stock changes. Every change updates three things together:
 * the per-warehouse row (warehouse_stocks), the product's total
 * (products.stock_quantity), and a ledger line (stock_movements).
 */
class StockService
{
    /**
     * @param  float  $qty  positive adds stock, negative removes it
     * @param  bool  $allowNegative  sales may oversell; purchasing documents may not
     */
    public function adjust(
        Product $product,
        int $warehouseId,
        float $qty,
        string $type,
        ?string $note = null,
        ?Model $reference = null,
        bool $allowNegative = true,
    ): void {
        if ($qty == 0.0) {
            return;
        }

        DB::transaction(function () use ($product, $warehouseId, $qty, $type, $note, $reference, $allowNegative) {
            $row = WarehouseStock::where('warehouse_id', $warehouseId)
                ->where('product_id', $product->id)
                ->lockForUpdate()
                ->first()
                ?? WarehouseStock::create(['warehouse_id' => $warehouseId, 'product_id' => $product->id, 'quantity' => 0]);

            if (! $allowNegative && (float) $row->quantity + $qty < -0.0005) {
                $warehouse = Warehouse::find($warehouseId);
                throw ValidationException::withMessages([
                    'items' => sprintf(
                        'Not enough %s in %s: %s in stock, %s needed.',
                        $product->name,
                        $warehouse?->name ?? 'the warehouse',
                        $this->format((float) $row->quantity),
                        $this->format(abs($qty)),
                    ),
                ]);
            }

            $row->increment('quantity', $qty);
            $product->increment('stock_quantity', $qty);

            StockMovement::create([
                'product_id' => $product->id,
                'warehouse_id' => $warehouseId,
                'user_id' => auth()->id(),
                'type' => $type,
                'qty' => $qty,
                'note' => $note,
                'reference_type' => $reference?->getMorphClass(),
                'reference_id' => $reference?->getKey(),
            ]);
        });
    }

    public function available(Product $product, int $warehouseId): float
    {
        return (float) (WarehouseStock::where('warehouse_id', $warehouseId)->where('product_id', $product->id)->value('quantity') ?? 0);
    }

    /** Deducts a product's stock from the default warehouse the moment it's added to a sale. */
    public function deductForSaleItem(SaleItem $saleItem): void
    {
        $product = $saleItem->product;

        if (! $product->track_stock) {
            return;
        }

        $this->adjust($product, Warehouse::default()->id, -(float) $saleItem->quantity, 'sale', "Sale item #{$saleItem->id}", $saleItem);
    }

    /** Restores stock when a line item is removed from an open sale before it's billed. */
    public function restoreForRemovedSaleItem(SaleItem $saleItem): void
    {
        $product = $saleItem->product;

        if (! $product->track_stock) {
            return;
        }

        $this->adjust($product, Warehouse::default()->id, (float) $saleItem->quantity, 'adjustment', "Removed sale item #{$saleItem->id}");
    }

    private function format(float $qty): string
    {
        return rtrim(rtrim(number_format($qty, 3, '.', ''), '0'), '.');
    }
}
