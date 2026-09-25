<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\Ingredient;
use App\Models\OrderItem;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ReportController extends Controller
{
    /** Daily sales + top-selling items, for a given date (defaults to today). */
    public function sales(Request $request): View
    {
        $date = $request->query('date') ? Carbon::parse($request->query('date')) : today();

        $bills = Bill::whereDate('created_at', $date)->where('status', 'paid')->get();

        $topItems = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereDate('order_items.created_at', $date)
            ->selectRaw('menu_item_id, SUM(quantity) as qty')
            ->groupBy('menu_item_id')
            ->orderByDesc('qty')
            ->with('menuItem')
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
        $ingredients = Ingredient::whereColumn('stock_qty', '<=', 'low_stock_threshold')->get();

        return view('admin.reports.low-stock', compact('ingredients'));
    }
}
