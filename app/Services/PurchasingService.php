<?php

namespace App\Services;

use App\Models\Grn;
use App\Models\GrnItem;
use App\Models\GrnReturn;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Purchase orders, goods received (GRN) and returns to supplier.
 * Purchases never move stock; GRNs add it; returns take it back out.
 * Editing a GRN or return reverses its old stock effect, then applies the new one.
 */
class PurchasingService
{
    public function __construct(private StockService $stock) {}

    /* ---------------------------------------------------------------- Purchases */

    /**
     * @param  array{supplier_id:int, warehouse_id:int, purchase_date:string, expected_date:?string, status:string,
     *               discount:?float, tax:?float, shipping:?float, notes:?string,
     *               items: array<int, array{product_id:int, quantity:float, unit_cost:float}>}  $data
     */
    public function savePurchase(array $data, ?Purchase $purchase = null): Purchase
    {
        return DB::transaction(function () use ($data, $purchase) {
            $lines = $this->lines($data['items']);
            $subtotal = array_sum(array_column($lines, 'line_total'));
            $discount = (float) ($data['discount'] ?? 0);
            $tax = (float) ($data['tax'] ?? 0);
            $shipping = (float) ($data['shipping'] ?? 0);

            $header = [
                'supplier_id' => $data['supplier_id'],
                'warehouse_id' => $data['warehouse_id'],
                'purchase_date' => $data['purchase_date'],
                'expected_date' => $data['expected_date'] ?? null,
                'status' => $data['status'],
                'subtotal' => $subtotal,
                'discount' => $discount,
                'tax' => $tax,
                'shipping' => $shipping,
                'total' => max($subtotal - $discount + $tax + $shipping, 0),
                'notes' => $data['notes'] ?? null,
            ];

            if ($purchase) {
                $purchase->update($header);
                $purchase->items()->delete();
            } else {
                $purchase = Purchase::create($header + ['created_by' => auth()->id()]);
                $purchase->update(['reference_no' => 'PO-'.str_pad((string) $purchase->id, 6, '0', STR_PAD_LEFT)]);
            }

            $purchase->items()->createMany($lines);

            return $purchase;
        });
    }

    /** Recomputes received quantities from GRNs and moves the status between ordered / partial / received. */
    public function refreshPurchaseStatus(?Purchase $purchase): void
    {
        if (! $purchase || $purchase->status === 'cancelled') {
            return;
        }

        $purchase->load('items');
        foreach ($purchase->items as $item) {
            $item->update(['received_quantity' => GrnItem::where('purchase_item_id', $item->id)->sum('quantity')]);
        }

        $received = $purchase->items->sum(fn (PurchaseItem $i) => (float) $i->received_quantity);
        $allIn = $purchase->items->every(fn (PurchaseItem $i) => (float) $i->received_quantity >= (float) $i->quantity);

        $status = match (true) {
            $received <= 0 => $purchase->status === 'draft' ? 'draft' : 'ordered',
            $allIn => 'received',
            default => 'partial',
        };

        $purchase->update(['status' => $status]);
    }

    /* --------------------------------------------------------------------- GRNs */

    /**
     * @param  array{purchase_id:?int, supplier_id:int, warehouse_id:int, received_date:string,
     *               supplier_invoice_no:?string, notes:?string,
     *               items: array<int, array{product_id:int, quantity:float, unit_cost:float, purchase_item_id:?int}>}  $data
     */
    public function saveGrn(array $data, ?Grn $grn = null): Grn
    {
        return DB::transaction(function () use ($data, $grn) {
            $oldPurchase = $grn?->purchase;

            if ($grn) {
                if (! $grn->isEditable()) {
                    throw ValidationException::withMessages(['items' => 'Goods have been returned from this GRN or a payment recorded against it, so it can no longer be changed.']);
                }
                $this->reverseGrnStock($grn);
                $grn->items()->delete();
            }

            $lines = $this->lines($data['items'], withPurchaseItem: true);
            $header = [
                'purchase_id' => $data['purchase_id'] ?? null,
                'supplier_id' => $data['supplier_id'],
                'warehouse_id' => $data['warehouse_id'],
                'received_date' => $data['received_date'],
                'supplier_invoice_no' => $data['supplier_invoice_no'] ?? null,
                'notes' => $data['notes'] ?? null,
                'total' => array_sum(array_column($lines, 'line_total')),
            ];

            if ($grn) {
                $grn->update($header);
            } else {
                $grn = Grn::create($header + ['created_by' => auth()->id()]);
                $grn->update(['grn_no' => 'GRN-'.str_pad((string) $grn->id, 6, '0', STR_PAD_LEFT)]);
            }

            foreach ($grn->items()->createMany($lines) as $item) {
                $product = Product::findOrFail($item->product_id);
                $this->stock->adjust($product, $grn->warehouse_id, (float) $item->quantity, 'goods_received', $grn->grn_no, $grn);
                // Latest cost paid becomes the product's purchase price.
                $product->update(['purchase_price' => $item->unit_cost]);
            }

            $this->refreshPurchaseStatus($grn->fresh()->purchase);
            if ($oldPurchase && $oldPurchase->id !== $grn->purchase_id) {
                $this->refreshPurchaseStatus($oldPurchase);
            }

            return $grn;
        });
    }

    public function deleteGrn(Grn $grn): void
    {
        DB::transaction(function () use ($grn) {
            if (! $grn->isEditable()) {
                throw ValidationException::withMessages(['items' => 'This GRN has returns or payments against it. Delete those first.']);
            }

            $purchase = $grn->purchase;
            $this->reverseGrnStock($grn);
            $grn->delete();
            $this->refreshPurchaseStatus($purchase);
        });
    }

    /** Takes a GRN's stock back out (before an edit or delete). Fails if it's already been sold. */
    private function reverseGrnStock(Grn $grn): void
    {
        foreach ($grn->items()->with('product')->get() as $item) {
            $this->stock->adjust(
                $item->product, $grn->warehouse_id, -(float) $item->quantity, 'goods_received',
                "Reversed {$grn->grn_no}", $grn, allowNegative: false,
            );
        }
    }

    /* ------------------------------------------------------------------ Returns */

    /**
     * @param  array{return_date:string, reason:?string, items: array<int, array{grn_item_id:int, quantity:float}>}  $data
     */
    public function saveReturn(Grn $grn, array $data, ?GrnReturn $return = null): GrnReturn
    {
        return DB::transaction(function () use ($grn, $data, $return) {
            if ($return) {
                $this->reverseReturn($return);
                $return->items()->delete();
            }

            $grnItems = $grn->items()->with('product')->get()->keyBy('id');
            $lines = [];
            foreach ($data['items'] as $index => $row) {
                $qty = round((float) ($row['quantity'] ?? 0), 3);
                if ($qty <= 0) {
                    continue;
                }

                $grnItem = $grnItems->get((int) $row['grn_item_id']);
                if (! $grnItem) {
                    throw ValidationException::withMessages(["items.$index.quantity" => 'That item isn\'t on this GRN.']);
                }
                if ($qty > $grnItem->returnableQuantity() + 0.0005) {
                    throw ValidationException::withMessages([
                        "items.$index.quantity" => "Only {$this->format($grnItem->returnableQuantity())} of {$grnItem->product->name} can still be returned.",
                    ]);
                }

                $lines[] = ['grn_item' => $grnItem, 'quantity' => $qty];
            }

            if ($lines === []) {
                throw ValidationException::withMessages(['items' => 'Enter a quantity for at least one item to return.']);
            }

            $header = [
                'grn_id' => $grn->id,
                'return_date' => $data['return_date'],
                'reason' => $data['reason'] ?? null,
                'total' => array_sum(array_map(fn ($l) => round($l['quantity'] * (float) $l['grn_item']->unit_cost, 2), $lines)),
            ];

            if ($return) {
                $return->update($header);
            } else {
                $return = GrnReturn::create($header + ['created_by' => auth()->id()]);
                $return->update(['return_no' => 'RET-'.str_pad((string) $return->id, 6, '0', STR_PAD_LEFT)]);
            }

            foreach ($lines as $line) {
                /** @var GrnItem $grnItem */
                $grnItem = $line['grn_item'];
                $return->items()->create([
                    'grn_item_id' => $grnItem->id,
                    'product_id' => $grnItem->product_id,
                    'quantity' => $line['quantity'],
                    'unit_cost' => $grnItem->unit_cost,
                    'line_total' => round($line['quantity'] * (float) $grnItem->unit_cost, 2),
                ]);
                $this->stock->adjust(
                    $grnItem->product, $grn->warehouse_id, -$line['quantity'], 'purchase_return',
                    "{$return->return_no} (from {$grn->grn_no})", $return, allowNegative: false,
                );
                $grnItem->increment('returned_quantity', $line['quantity']);
            }

            return $return;
        });
    }

    public function deleteReturn(GrnReturn $return): void
    {
        DB::transaction(function () use ($return) {
            $this->reverseReturn($return);
            $return->delete();
        });
    }

    /** Puts returned goods back into stock and un-marks them as returned on the GRN. */
    private function reverseReturn(GrnReturn $return): void
    {
        $grn = $return->grn;
        foreach ($return->items()->with(['product', 'grnItem'])->get() as $item) {
            $this->stock->adjust($item->product, $grn->warehouse_id, (float) $item->quantity, 'purchase_return', "Reversed {$return->return_no}", $return);
            $item->grnItem->decrement('returned_quantity', (float) $item->quantity);
        }
    }

    /* ------------------------------------------------------------------ Helpers */

    /** Normalises submitted line items: drops empty rows, rounds, computes line totals. */
    private function lines(array $items, bool $withPurchaseItem = false): array
    {
        $lines = [];
        foreach ($items as $row) {
            $qty = round((float) ($row['quantity'] ?? 0), 3);
            if (empty($row['product_id']) || $qty <= 0) {
                continue;
            }
            $cost = round((float) ($row['unit_cost'] ?? 0), 2);
            $line = [
                'product_id' => (int) $row['product_id'],
                'quantity' => $qty,
                'unit_cost' => $cost,
                'line_total' => round($qty * $cost, 2),
            ];
            if ($withPurchaseItem) {
                $line['purchase_item_id'] = ! empty($row['purchase_item_id']) ? (int) $row['purchase_item_id'] : null;
            }
            $lines[] = $line;
        }

        if ($lines === []) {
            throw ValidationException::withMessages(['items' => 'Add at least one item with a quantity above zero.']);
        }

        return $lines;
    }

    private function format(float $qty): string
    {
        return rtrim(rtrim(number_format($qty, 3, '.', ''), '0'), '.');
    }
}
