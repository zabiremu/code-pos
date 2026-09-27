<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Warehouse;
use App\Services\ReportService;
use App\Support\DateRange;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(private ReportService $reports) {}

    public function sales(Request $request): View
    {
        $range = DateRange::fromRequest($request);

        return view('admin.reports.sales', ['range' => $range] + $this->reports->sales($range));
    }

    public function profitLoss(Request $request): View
    {
        $range = DateRange::fromRequest($request);

        return view('admin.reports.profit-loss', ['range' => $range] + $this->reports->profitLoss($range));
    }

    public function stockValue(Request $request): View
    {
        $warehouseId = $request->integer('warehouse') ?: null;

        return view('admin.reports.stock-value', [
            'warehouseId' => $warehouseId,
            'warehouses' => Warehouse::orderByDesc('is_default')->orderBy('name')->get(['id', 'name']),
        ] + $this->reports->stockValue($warehouseId));
    }

    public function purchases(Request $request): View
    {
        $range = DateRange::fromRequest($request);

        return view('admin.reports.purchases', ['range' => $range] + $this->reports->purchases($range));
    }

    public function lowStock(): View
    {
        $products = Product::with('unit:id,short_name')->where('track_stock', true)
            ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
            ->orderBy('stock_quantity')
            ->get();

        return view('admin.reports.low-stock', compact('products'));
    }
}
