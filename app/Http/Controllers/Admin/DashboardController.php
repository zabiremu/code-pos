<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\Branch;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    /** Today's headline numbers, the hourly revenue line, and what needs attention. */
    public function __invoke(): View
    {
        $todaySales = Sale::whereDate('created_at', today())->count();
        $openSales = Sale::whereIn('status', ['open', 'billed'])->count();
        $lowStockQuery = Product::where('track_stock', true)->whereColumn('stock_quantity', '<=', 'low_stock_threshold');
        $lowStockCount = (clone $lowStockQuery)->count();

        // Paid bills today, bucketed by hour in PHP so it works the same on
        // MySQL and SQLite (tests) without DB-specific HOUR() functions.
        $paidToday = Bill::whereDate('created_at', today())->where('status', 'paid')->get(['grand_total', 'created_at']);
        $hourlyRevenue = array_fill(0, 24, 0.0);
        foreach ($paidToday as $bill) {
            $hourlyRevenue[(int) $bill->created_at->format('G')] += (float) $bill->grand_total;
        }
        $todayRevenue = array_sum($hourlyRevenue);

        // Yesterday up to the same time of day, so the comparison is fair.
        $yesterdaySoFar = (float) Bill::where('status', 'paid')
            ->whereBetween('created_at', [today()->subDay(), now()->subDay()])
            ->sum('grand_total');

        return view('admin.dashboard', [
            'todaySales' => $todaySales,
            'openSales' => $openSales,
            'todayRevenue' => $todayRevenue,
            'yesterdaySoFar' => $yesterdaySoFar,
            'hourlyRevenue' => $hourlyRevenue,
            'currentHour' => (int) now()->format('G'),
            'currency' => Branch::first()?->currency,
            'lowStockCount' => $lowStockCount,
            'lowStockProducts' => $lowStockQuery->orderBy('stock_quantity')->take(5)->get(['id', 'name', 'stock_quantity', 'low_stock_threshold']),
            'recentSales' => Sale::with('cashier:id,name')->withCount('items')->latest()->take(6)->get(),
        ]);
    }
}
