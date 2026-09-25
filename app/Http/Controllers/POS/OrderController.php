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
    public function index(): View
    {
        $orders = Order::with(['table', 'waiter', 'items.menuItem'])
            ->whereIn('status', ['open', 'sent', 'served'])
            ->latest()
            ->paginate(20);

        return view('pos.orders.index', compact('orders'));
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
