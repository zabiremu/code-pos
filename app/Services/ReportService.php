<?php

namespace App\Services;

use App\Models\Bill;
use App\Models\Expense;
use App\Models\Grn;
use App\Models\GrnReturn;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\SaleItem;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\StockAdjustmentItem;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\WarehouseStock;
use App\Support\DateRange;
use Illuminate\Support\Facades\DB;

/**
 * Numbers for the Reports section. Everything is dated by when it
 * happened (bill created, return/expense/GRN date), not when it was paid.
 * Grouping by day is done in PHP so it works the same on MySQL and SQLite.
 */
class ReportService
{
    /* -------------------------------------------------------------------- Sales */

    public function sales(DateRange $range): array
    {
        $bills = Bill::with('sale:id,cashier_id', 'sale.cashier:id,name')
            ->whereBetween('created_at', $range->between())
            ->where('status', '!=', 'void')
            ->get(['id', 'sale_id', 'subtotal', 'tax_total', 'discount_total', 'grand_total', 'created_at']);

        $gross = (float) $bills->sum('grand_total');
        $saleIds = $bills->pluck('sale_id')->unique();

        $paidOnTheseBills = (float) Payment::whereIn('bill_id', $bills->pluck('id'))->sum('amount');
        $adjusted = (float) SaleReturn::whereIn('bill_id', $bills->pluck('id'))->where('refund_method', 'adjust_due')->sum('total');

        $topProducts = SaleItem::query()
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->whereIn('sale_items.sale_id', $saleIds)
            ->groupBy('sale_items.product_id', 'products.name')
            ->selectRaw('sale_items.product_id, products.name, sum(sale_items.quantity) as qty,
                sum(sale_items.quantity * sale_items.unit_price) as revenue,
                sum(sale_items.quantity * sale_items.unit_cost) as cost')
            ->orderByDesc('revenue')
            ->limit(15)
            ->get();

        $daily = [];
        for ($d = $range->from->copy(); $d->lte($range->to) && count($daily) < 370; $d->addDay()) {
            $daily[$d->toDateString()] = 0.0;
        }
        foreach ($bills as $b) {
            $key = $b->created_at->toDateString();
            if (array_key_exists($key, $daily)) {
                $daily[$key] += (float) $b->grand_total;
            }
        }

        return [
            'count' => $bills->count(),
            'gross' => $gross,
            'tax' => (float) $bills->sum('tax_total'),
            'discount' => (float) $bills->sum('discount_total'),
            'average' => $bills->count() ? $gross / $bills->count() : 0,
            'returns' => (float) SaleReturn::whereBetween('return_date', $range->betweenDates())->sum('total'),
            'onCredit' => max(round($gross - $paidOnTheseBills - $adjusted, 2), 0),
            'byMethod' => Payment::whereBetween('created_at', $range->between())
                ->groupBy('method')->selectRaw('method, sum(amount) as total')->orderByDesc('total')->pluck('total', 'method'),
            'topProducts' => $topProducts,
            'byCashier' => $bills->groupBy(fn ($b) => $b->sale?->cashier?->name ?? 'Unknown')
                ->map(fn ($g) => ['count' => $g->count(), 'total' => (float) $g->sum('grand_total')])
                ->sortByDesc('total'),
            'daily' => $daily,
        ];
    }

    /* ------------------------------------------------------------ Profit & loss */

    public function profitLoss(DateRange $range): array
    {
        $bills = Bill::whereBetween('created_at', $range->between())->where('status', '!=', 'void')
            ->get(['id', 'sale_id', 'grand_total', 'tax_total']);

        $sales = (float) $bills->sum(fn ($b) => (float) $b->grand_total - (float) $b->tax_total);
        $tax = (float) $bills->sum('tax_total');

        // Returns in the period, without the tax part (tax isn't the shop's income).
        $returns = SaleReturn::with('bill:id,grand_total,tax_total')->whereBetween('return_date', $range->betweenDates())->get();
        $returnsNet = (float) $returns->sum(function ($r) {
            $grand = (float) $r->bill?->grand_total;

            return $grand > 0 ? (float) $r->total * (1 - (float) $r->bill->tax_total / $grand) : (float) $r->total;
        });

        $cogs = (float) SaleItem::whereIn('sale_id', $bills->pluck('sale_id')->unique())
            ->sum(DB::raw('quantity * unit_cost'));

        // Restocked returns come back into stock at what they cost.
        $returnedCost = (float) SaleReturnItem::query()
            ->join('sale_returns', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
            ->join('sale_items', 'sale_items.id', '=', 'sale_return_items.sale_item_id')
            ->whereIn('sale_returns.id', $returns->where('restock', true)->pluck('id'))
            ->sum(DB::raw('sale_return_items.quantity * sale_items.unit_cost'));

        $netSales = $sales - $returnsNet;
        $costOfSales = $cogs - $returnedCost;
        $grossProfit = $netSales - $costOfSales;

        $expenses = Expense::whereBetween('expense_date', $range->betweenDates())
            ->join('expense_categories', 'expense_categories.id', '=', 'expenses.expense_category_id')
            ->groupBy('expense_categories.name')->selectRaw('expense_categories.name, sum(expenses.amount) as total')
            ->orderByDesc('total')->pluck('total', 'name')->map(fn ($v) => (float) $v);

        // Damaged / lost / counted stock, valued at cost. Negative = a loss.
        $stockAdjustments = (float) StockAdjustmentItem::query()
            ->join('stock_adjustments', 'stock_adjustments.id', '=', 'stock_adjustment_items.stock_adjustment_id')
            ->whereBetween('stock_adjustments.adjustment_date', $range->betweenDates())
            ->sum(DB::raw('stock_adjustment_items.quantity * stock_adjustment_items.unit_cost'));

        $totalExpenses = (float) $expenses->sum();
        $netProfit = $grossProfit - $totalExpenses + $stockAdjustments;

        return [
            'sales' => $sales,
            'returns' => $returnsNet,
            'netSales' => $netSales,
            'cogs' => $cogs,
            'returnedCost' => $returnedCost,
            'costOfSales' => $costOfSales,
            'grossProfit' => $grossProfit,
            'grossMargin' => $netSales > 0 ? $grossProfit / $netSales * 100 : null,
            'expenses' => $expenses,
            'totalExpenses' => $totalExpenses,
            'stockAdjustments' => $stockAdjustments,
            'netProfit' => $netProfit,
            'netMargin' => $netSales > 0 ? $netProfit / $netSales * 100 : null,
            'tax' => $tax,
            'missingCost' => SaleItem::whereIn('sale_id', $bills->pluck('sale_id')->unique())->where('unit_cost', 0)->count(),
        ];
    }

    /* --------------------------------------------------------------- Stock value */

    public function stockValue(?int $warehouseId): array
    {
        $products = Product::with('category:id,name', 'unit:id,short_name')
            ->where('track_stock', true)
            ->get(['id', 'name', 'sku', 'category_id', 'unit_id', 'purchase_price', 'base_price', 'stock_quantity']);

        $quantities = $warehouseId
            ? WarehouseStock::where('warehouse_id', $warehouseId)->pluck('quantity', 'product_id')->map(fn ($q) => (float) $q)
            : $products->mapWithKeys(fn ($p) => [$p->id => (float) $p->stock_quantity]);

        $rows = $products->map(function ($p) use ($quantities) {
            $qty = (float) ($quantities[$p->id] ?? 0);
            $counted = max($qty, 0); // negative stock has no value

            return [
                'product' => $p,
                'qty' => $qty,
                'cost' => $counted * (float) $p->purchase_price,
                'retail' => $counted * (float) $p->base_price,
            ];
        })->filter(fn ($r) => $r['qty'] != 0)->sortByDesc('cost')->values();

        return [
            'rows' => $rows,
            'cost' => (float) $rows->sum('cost'),
            'retail' => (float) $rows->sum('retail'),
            'units' => (float) $rows->sum(fn ($r) => max($r['qty'], 0)),
            'noCost' => $rows->filter(fn ($r) => (float) $r['product']->purchase_price <= 0 && $r['qty'] > 0)->count(),
            'byCategory' => $rows->groupBy(fn ($r) => $r['product']->category?->name ?? 'Uncategorised')
                ->map(fn ($g) => (float) $g->sum('cost'))->sortDesc(),
        ];
    }

    /* ----------------------------------------------------------------- Purchases */

    public function purchases(DateRange $range): array
    {
        $grns = Grn::whereBetween('received_date', $range->betweenDates())->get(['id', 'supplier_id', 'total']);
        $returns = GrnReturn::with('grn:id,supplier_id')->whereBetween('return_date', $range->betweenDates())->get(['id', 'grn_id', 'total']);
        $payments = SupplierPayment::whereBetween('payment_date', $range->betweenDates())->get(['supplier_id', 'amount']);

        $supplierIds = $grns->pluck('supplier_id')->merge($returns->pluck('grn.supplier_id'))->merge($payments->pluck('supplier_id'))->filter()->unique();
        $suppliers = Supplier::whereIn('id', $supplierIds)->get()->keyBy('id');

        $bySupplier = $supplierIds->map(fn ($id) => [
            'supplier' => $suppliers[$id] ?? null,
            'received' => (float) $grns->where('supplier_id', $id)->sum('total'),
            'returned' => (float) $returns->filter(fn ($r) => $r->grn?->supplier_id == $id)->sum('total'),
            'paid' => (float) $payments->where('supplier_id', $id)->sum('amount'),
            'owedNow' => $suppliers[$id]?->balance() ?? 0,
        ])->sortByDesc('received')->values();

        return [
            'grnCount' => $grns->count(),
            'received' => (float) $grns->sum('total'),
            'returned' => (float) $returns->sum('total'),
            'paid' => (float) $payments->sum('amount'),
            'bySupplier' => $bySupplier,
            'openOrders' => Purchase::whereIn('status', ['draft', 'ordered', 'partial'])->count(),
            'openOrdersValue' => (float) Purchase::whereIn('status', ['draft', 'ordered', 'partial'])->sum('total'),
        ];
    }
}
