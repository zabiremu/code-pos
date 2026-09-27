<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\StockTransfer;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Stock transfers between warehouses and stock adjustments (write-offs,
 * count corrections). Like GRNs, editing one reverses its old effect first.
 */
class InventoryService
{
    public function __construct(private StockService $stock) {}

    /* ---------------------------------------------------------------- Transfers */

    /** @param array{from_warehouse_id:int, to_warehouse_id:int, transfer_date:string, notes:?string, items:array} $data */
    public function saveTransfer(array $data, ?StockTransfer $transfer = null): StockTransfer
    {
        return DB::transaction(function () use ($data, $transfer) {
            if ($transfer) {
                $this->reverseTransfer($transfer);
                $transfer->items()->delete();
            }

            $lines = $this->mergeLines($data['items']);
            $header = [
                'from_warehouse_id' => $data['from_warehouse_id'],
                'to_warehouse_id' => $data['to_warehouse_id'],
                'transfer_date' => $data['transfer_date'],
                'notes' => $data['notes'] ?? null,
            ];

            if ($transfer) {
                $transfer->update($header);
            } else {
                $transfer = StockTransfer::create($header + ['created_by' => auth()->id()]);
                $transfer->update(['transfer_no' => 'TRF-'.str_pad((string) $transfer->id, 6, '0', STR_PAD_LEFT)]);
            }

            foreach ($lines as $productId => $qty) {
                $product = Product::findOrFail($productId);
                $transfer->items()->create(['product_id' => $productId, 'quantity' => $qty]);
                $this->stock->adjust($product, (int) $transfer->from_warehouse_id, -$qty, 'transfer', "{$transfer->transfer_no} out", $transfer, allowNegative: false);
                $this->stock->adjust($product, (int) $transfer->to_warehouse_id, $qty, 'transfer', "{$transfer->transfer_no} in", $transfer);
            }

            return $transfer;
        });
    }

    public function deleteTransfer(StockTransfer $transfer): void
    {
        DB::transaction(function () use ($transfer) {
            $this->reverseTransfer($transfer);
            $transfer->delete();
        });
    }

    /** Sends a transfer's stock back where it came from. Fails if it's already been used at the destination. */
    private function reverseTransfer(StockTransfer $transfer): void
    {
        foreach ($transfer->items()->with('product')->get() as $item) {
            $qty = (float) $item->quantity;
            $this->stock->adjust($item->product, (int) $transfer->to_warehouse_id, -$qty, 'transfer', "Reversed {$transfer->transfer_no}", $transfer, allowNegative: false);
            $this->stock->adjust($item->product, (int) $transfer->from_warehouse_id, $qty, 'transfer', "Reversed {$transfer->transfer_no}", $transfer);
        }
    }

    /* -------------------------------------------------------------- Adjustments */

    /**
     * Lines arrive as {product_id, direction: add|remove, quantity}, or for a
     * stock count as {product_id, counted} - the difference from what the
     * system holds becomes the adjustment.
     *
     * @param array{warehouse_id:int, adjustment_date:string, reason:string, notes:?string, items:array} $data
     */
    public function saveAdjustment(array $data, ?StockAdjustment $adjustment = null): StockAdjustment
    {
        return DB::transaction(function () use ($data, $adjustment) {
            if ($adjustment) {
                $this->reverseAdjustment($adjustment);
                $adjustment->items()->delete();
            }

            $header = [
                'warehouse_id' => $data['warehouse_id'],
                'adjustment_date' => $data['adjustment_date'],
                'reason' => $data['reason'],
                'notes' => $data['notes'] ?? null,
            ];

            if ($adjustment) {
                $adjustment->update($header);
            } else {
                $adjustment = StockAdjustment::create($header + ['created_by' => auth()->id()]);
                $adjustment->update(['adjustment_no' => 'ADJ-'.str_pad((string) $adjustment->id, 6, '0', STR_PAD_LEFT)]);
            }

            $warehouseId = (int) $adjustment->warehouse_id;
            $created = 0;

            foreach ($data['items'] as $row) {
                if (empty($row['product_id'])) {
                    continue;
                }
                $product = Product::findOrFail((int) $row['product_id']);

                if ($data['reason'] === 'count') {
                    if (! isset($row['counted']) || $row['counted'] === '') {
                        continue;
                    }
                    $qty = round((float) $row['counted'] - $this->stock->available($product, $warehouseId), 3);
                } else {
                    $qty = round((float) ($row['quantity'] ?? 0), 3);
                    if (($row['direction'] ?? 'remove') === 'remove') {
                        $qty = -$qty;
                    }
                }

                if ($qty == 0.0) {
                    continue;
                }

                $adjustment->items()->create([
                    'product_id' => $product->id,
                    'quantity' => $qty,
                    'unit_cost' => $product->purchase_price,
                ]);
                $this->stock->adjust(
                    $product, $warehouseId, $qty, 'adjustment',
                    "{$adjustment->adjustment_no} ({$adjustment->reasonLabel()})", $adjustment,
                    allowNegative: false,
                );
                $created++;
            }

            if ($created === 0) {
                throw ValidationException::withMessages([
                    'items' => $data['reason'] === 'count'
                        ? 'Every counted quantity matches the system, so there is nothing to adjust.'
                        : 'Add at least one product with a quantity above zero.',
                ]);
            }

            return $adjustment;
        });
    }

    public function deleteAdjustment(StockAdjustment $adjustment): void
    {
        DB::transaction(function () use ($adjustment) {
            $this->reverseAdjustment($adjustment);
            $adjustment->delete();
        });
    }

    private function reverseAdjustment(StockAdjustment $adjustment): void
    {
        foreach ($adjustment->items()->with('product')->get() as $item) {
            $this->stock->adjust(
                $item->product, (int) $adjustment->warehouse_id, -(float) $item->quantity, 'adjustment',
                "Reversed {$adjustment->adjustment_no}", $adjustment, allowNegative: false,
            );
        }
    }

    /** product_id => total quantity, dropping empty rows and merging duplicates. */
    private function mergeLines(array $items): array
    {
        $lines = [];
        foreach ($items as $row) {
            $qty = round((float) ($row['quantity'] ?? 0), 3);
            if (empty($row['product_id']) || $qty <= 0) {
                continue;
            }
            $lines[(int) $row['product_id']] = ($lines[(int) $row['product_id']] ?? 0) + $qty;
        }

        if ($lines === []) {
            throw ValidationException::withMessages(['items' => 'Add at least one product with a quantity above zero.']);
        }

        return $lines;
    }
}
