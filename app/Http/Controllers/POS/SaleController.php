<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Cashier-facing sale taking. Line items are handled by SaleItemController;
 * this controller owns the sale lifecycle (open -> billed -> closed).
 */
class SaleController extends Controller
{
    /**
     * Status-tab + search filtering follows the same shape as a WordPress
     * list-table screen (All | Open | Billed tabs with counts, a search
     * box, ?status=&q= in the URL) rather than a single flat list.
     */
    public function index(Request $request): View
    {
        $activeStatuses = ['open', 'billed'];
        $status = $request->query('status', 'all');
        $search = trim((string) $request->query('q', ''));

        $sales = Sale::with(['cashier', 'items.product'])
            ->whereIn('status', $activeStatuses)
            ->when(in_array($status, $activeStatuses, true), fn ($q) => $q->where('status', $status))
            ->when($search !== '', fn ($q) => $q->where('id', 'like', "%{$search}%"))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $counts = [
            'all' => Sale::whereIn('status', $activeStatuses)->count(),
            'open' => Sale::where('status', 'open')->count(),
            'billed' => Sale::where('status', 'billed')->count(),
        ];

        return view('pos.sales.index', compact('sales', 'counts', 'status', 'search'));
    }

    /** Starts a new, empty sale for the current cashier - no setup form needed. */
    public function store(Request $request): RedirectResponse
    {
        $sale = Sale::create([
            'cashier_id' => $request->user()->id,
            'status' => 'open',
        ]);

        return redirect()->route('pos.sales.show', $sale);
    }

    public function show(Sale $sale): View
    {
        $sale->load(['items.product']);
        $products = Product::where('is_available', true)->orderBy('name')->get();

        return view('pos.sales.show', compact('sale', 'products'));
    }

    /** Marks the sale closed, once its bill(s) are fully paid. */
    public function close(Sale $sale): RedirectResponse
    {
        if ($sale->bills()->where('status', '!=', 'paid')->exists()) {
            return back()->withErrors(['sale' => 'This sale still has an unpaid bill.']);
        }

        $sale->update(['status' => 'closed']);

        return redirect()->route('pos.sales.index')->with('status', "Sale #{$sale->id} closed.");
    }
}
