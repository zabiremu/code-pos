<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Models\DiningTable;
use App\Models\MenuItem;
use App\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Waiter-facing order taking. Line items are handled by OrderItemController;
 * this controller owns the order lifecycle (open -> sent -> served -> billed
 * -> closed) and keeps the table's floor-plan status in sync with it.
 */
class OrderController extends Controller
{
    /**
     * Status-tab + search filtering follows the same shape as a WordPress
     * list-table screen (All | Open | Sent | Served tabs with counts, a
     * search box, ?status=&q= in the URL) rather than a single flat list.
     */
    public function index(Request $request): View
    {
        $activeStatuses = ['open', 'sent', 'served'];
        $status = $request->query('status', 'all');
        $search = trim((string) $request->query('q', ''));

        $orders = Order::with(['table', 'waiter', 'items.menuItem'])
            ->whereIn('status', $activeStatuses)
            ->when(in_array($status, $activeStatuses, true), fn ($q) => $q->where('status', $status))
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                    ->orWhere('type', 'like', "%{$search}%")
                    ->orWhereHas('table', fn ($t) => $t->where('label', 'like', "%{$search}%"));
            }))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $counts = [
            'all' => Order::whereIn('status', $activeStatuses)->count(),
            'open' => Order::where('status', 'open')->count(),
            'sent' => Order::where('status', 'sent')->count(),
            'served' => Order::where('status', 'served')->count(),
        ];

        return view('pos.orders.index', compact('orders', 'counts', 'status', 'search'));
    }

    public function create(): View
    {
        $tables = DiningTable::where('status', 'free')->with('floor')->get();

        return view('pos.orders.create', compact('tables'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'table_id' => ['nullable', 'exists:tables,id'],
            'type' => ['required', 'in:dine_in,takeaway,delivery'],
            'guest_count' => ['required', 'integer', 'min:1'],
        ]);

        $order = Order::create($data + [
            'waiter_id' => $request->user()->id,
            'status' => 'open',
        ]);

        if ($order->table_id) {
            DiningTable::whereKey($order->table_id)->update(['status' => 'occupied']);
        }

        return redirect()->route('pos.orders.show', $order);
    }

    public function show(Order $order): View
    {
        $order->load(['table', 'items.menuItem', 'items.variant', 'items.modifiers']);
        $menuItems = MenuItem::where('is_available', true)->with(['variants', 'modifierGroups.modifiers'])->orderBy('name')->get();

        return view('pos.orders.show', compact('order', 'menuItems'));
    }

    /** Marks the order closed and frees its table, once its bill(s) are fully paid. */
    public function close(Order $order): RedirectResponse
    {
        if ($order->bills()->where('status', '!=', 'paid')->exists()) {
            return back()->withErrors(['order' => 'This order still has an unpaid bill.']);
        }

        $order->update(['status' => 'closed']);

        if ($order->table_id) {
            DiningTable::whereKey($order->table_id)->update(['status' => 'free']);
        }

        return redirect()->route('pos.orders.index')->with('status', "Order #{$order->id} closed.");
    }
}
