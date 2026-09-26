<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\Product;
use App\Models\SaleItem;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ReportController extends Controller
{
    /** Daily sales + top-selling products, for a given date (defaults to today). */
    public function sales(Request $request): View
    {
        $date = $request->query('date') ? Carbon::parse($request->query('date')) : today();

        $bills = Bill::whereDate('created_at', $date)->where('status', 'paid')->get();

        $topItems = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->whereDate('sale_items.created_at', $date)
            ->selectRaw('product_id, SUM(quantity) as qty')
            ->groupBy('product_id')
            ->orderByDesc('qty')
            ->with('product')
            ->limit(10)
            ->get();

        return view('admin.reports.sales', [
            'date' => $date,
            'totalSales' => $bills->sum('grand_total'),
            'billCount' => $bills->count(),
            'topItems' => $topItems,
        ]);
    }

    public function lowStock(): View
    {
        $products = Product::where('track_stock', true)
            ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
            ->get();

        return view('admin.reports.low-stock', compact('products'));
    }
}
