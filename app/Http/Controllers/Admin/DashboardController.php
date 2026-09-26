<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    /** Today's headline numbers for the admin landing page. */
    public function __invoke(): View
    {
        $todaySales = Sale::whereDate('created_at', today())->count();
        $openSales = Sale::whereIn('status', ['open', 'billed'])->count();
        $todayRevenue = Bill::whereDate('created_at', today())->where('status', 'paid')->sum('grand_total');
        $lowStockCount = Product::where('track_stock', true)->whereColumn('stock_quantity', '<=', 'low_stock_threshold')->count();

        return view('admin.dashboard', compact('todaySales', 'openSales', 'todayRevenue', 'lowStockCount'));
    }
}
