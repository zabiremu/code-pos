<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Waiter-facing order taking. This is the Phase 3 module from the build
 * plan (order taking + KDS) — scaffolded here with the core CRUD so the
 * feature work has a starting shape; ticket routing to the kitchen display
 * and course hold/fire logic land with the KDS work itself.
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

        return redirect()->route('pos.orders.show', $order);
    }

    public function show(Order $order): View
    {
        $order->load(['table', 'items.menuItem', 'items.variant', 'items.modifiers']);

        return view('pos.orders.show', compact('order'));
    }
}
