<?php

namespace App\Services;

use App\Models\Bill;
use App\Models\Customer;
use App\Models\CustomerReceipt;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Customer money: collecting what they owe, and goods they bring back.
 */
class CustomerAccountService
{
    public function __construct(private StockService $stock) {}

    /* ------------------------------------------------------------- Collections */

    /**
     * Records a due collection and spreads it over the customer's unpaid
     * bills, oldest first. Can't be more than they owe.
     *
     * @param  array{receipt_date:string, amount:float, method:string, reference:?string, notes:?string}  $data
     */
    public function collect(Customer $customer, array $data): CustomerReceipt
    {
        return DB::transaction(function () use ($customer, $data) {
            $amount = round((float) $data['amount'], 2);
            $bills = $customer->openBills();
            $due = round($bills->sum(fn (Bill $b) => $b->balanceDue()), 2);

            if ($due <= 0) {
                throw ValidationException::withMessages(['amount' => "{$customer->name} doesn't owe anything."]);
            }
            if ($amount > $due + 0.004) {
                throw ValidationException::withMessages(['amount' => 'They only owe '.number_format($due, 2).'.']);
            }

            $receipt = CustomerReceipt::create([
                'customer_id' => $customer->id,
                'receipt_date' => $data['receipt_date'],
                'amount' => $amount,
                'method' => $data['method'],
                'reference' => $data['reference'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);
            $receipt->update(['receipt_no' => 'COL-'.str_pad((string) $receipt->id, 6, '0', STR_PAD_LEFT)]);

            $left = $amount;
            foreach ($bills as $bill) {
                if ($left <= 0.004) {
                    break;
                }
                $portion = round(min($left, $bill->balanceDue()), 2);
                $bill->payments()->create([
                    'customer_receipt_id' => $receipt->id,
                    'method' => $data['method'],
                    'amount' => $portion,
                    'reference' => $receipt->receipt_no,
                    'received_by' => auth()->id(),
                ]);
                $bill->refreshStatus();
                $left = round($left - $portion, 2);
            }

            return $receipt;
        });
    }

    public function deleteReceipt(CustomerReceipt $receipt): void
    {
        DB::transaction(function () use ($receipt) {
            $bills = Bill::whereIn('id', $receipt->payments()->pluck('bill_id'))->get();
            $receipt->delete(); // its payments cascade
            $bills->each->refreshStatus();
        });
    }

    /* ------------------------------------------------------------------ Returns */

    /**
     * @param  array{return_date:string, refund_method:string, restock:bool, reason:?string,
     *               items: array<int, array{sale_item_id:int, quantity:int|string|null}>}  $data
     */
    public function saveReturn(Sale $sale, array $data): SaleReturn
    {
        return DB::transaction(function () use ($sale, $data) {
            $bill = $sale->bills()->with(['payments', 'saleReturns'])->latest('id')->first();
            if (! $bill) {
                throw ValidationException::withMessages(['items' => 'This sale was never billed, so there is nothing to return.']);
            }

            // Each unit gives back its share of tax and discount, not just the shelf price.
            $ratio = (float) $bill->subtotal > 0 ? (float) $bill->grand_total / (float) $bill->subtotal : 0;
            $saleItems = $sale->items()->with('product')->get()->keyBy('id');

            $lines = [];
            foreach ($data['items'] as $index => $row) {
                $qty = (int) ($row['quantity'] ?? 0);
                if ($qty <= 0) {
                    continue;
                }
                $item = $saleItems->get((int) $row['sale_item_id']);
                if (! $item) {
                    throw ValidationException::withMessages(["items.$index.quantity" => 'That item isn\'t on this sale.']);
                }
                if ($qty > $item->returnableQuantity()) {
                    throw ValidationException::withMessages([
                        "items.$index.quantity" => "Only {$item->returnableQuantity()} of {$item->product->name} can still be returned.",
                    ]);
                }
                $unit = round((float) $item->unit_price * $ratio, 2);
                $lines[] = ['item' => $item, 'qty' => $qty, 'unit' => $unit, 'total' => round($unit * $qty, 2)];
            }

            if ($lines === []) {
                throw ValidationException::withMessages(['items' => 'Enter a quantity for at least one item.']);
            }

            $total = round(array_sum(array_column($lines, 'total')), 2);

            if ($data['refund_method'] === 'adjust_due' && $total > $bill->balanceDue() + 0.004) {
                throw ValidationException::withMessages([
                    'refund_method' => 'Only '.number_format(max($bill->balanceDue(), 0), 2).' is owed on this bill. Refund the rest in cash instead.',
                ]);
            }

            $return = SaleReturn::create([
                'sale_id' => $sale->id,
                'bill_id' => $bill->id,
                'customer_id' => $sale->customer_id,
                'return_date' => $data['return_date'],
                'refund_method' => $data['refund_method'],
                'restock' => (bool) $data['restock'],
                'total' => $total,
                'reason' => $data['reason'] ?? null,
                'created_by' => auth()->id(),
            ]);
            $return->update(['return_no' => 'SR-'.str_pad((string) $return->id, 6, '0', STR_PAD_LEFT)]);

            $warehouseId = Warehouse::default()->id;
            foreach ($lines as $line) {
                $return->items()->create([
                    'sale_item_id' => $line['item']->id,
                    'product_id' => $line['item']->product_id,
                    'quantity' => $line['qty'],
                    'unit_refund' => $line['unit'],
                    'line_total' => $line['total'],
                ]);
                $line['item']->increment('returned_quantity', $line['qty']);

                if ($return->restock && $line['item']->product->track_stock) {
                    $this->stock->adjust($line['item']->product, $warehouseId, $line['qty'], 'sale_return', $return->return_no, $return);
                }
            }

            $bill->refreshStatus();

            return $return;
        });
    }

    public function deleteReturn(SaleReturn $return): void
    {
        DB::transaction(function () use ($return) {
            $warehouseId = Warehouse::default()->id;
            foreach ($return->items()->with(['product', 'saleItem'])->get() as $item) {
                if ($return->restock && $item->product->track_stock) {
                    $this->stock->adjust($item->product, $warehouseId, -(int) $item->quantity, 'sale_return', "Reversed {$return->return_no}", $return, allowNegative: false);
                }
                $item->saleItem->decrement('returned_quantity', (int) $item->quantity);
            }

            $bill = $return->bill;
            $return->delete();
            $bill->refreshStatus();
        });
    }
}
