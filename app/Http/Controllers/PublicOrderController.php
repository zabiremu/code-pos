<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\DiningTable;
use App\Models\MenuItem;
use App\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Customer-facing QR ordering - CodeCanyon differentiator feature. Scanning
 * a table's printed QR code opens this menu with no login required.
 * Submitting adds "pending" items to that table's open order (creating one
 * if none exists) exactly as if a waiter had typed them in via
 * Pos\OrderItemController::store - staff still review and "Send to
 * kitchen" from the normal POS screen, so nothing downstream (KDS,
 * billing) needed to change for this to be safe to turn on.
 */
class PublicOrderController extends Controller
{
    public function menu(DiningTable $table): View
    {
        $categories = Category::where('is_active', true)
            ->with(['menuItems' => fn ($q) => $q->where('is_available', true)->orderBy('name')])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->filter(fn ($category) => $category->menuItems->isNotEmpty())
            ->values();

        return view('public.order-menu', [
            'table' => $table,
            'categories' => $categories,
        ]);
    }

    public function store(Request $request, DiningTable $table): RedirectResponse
    {
        $cart = json_decode((string) $request->input('cart', '[]'), true);

        $validator = Validator::make(['items' => $cart, 'guest_name' => $request->input('guest_name')], [
            'items' => ['required', 'array', 'min:1'],
            'items.*.menu_item_id' => ['required', 'integer', 'exists:menu_items,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:20'],
            'items.*.notes' => ['nullable', 'string', 'max:255'],
            'guest_name' => ['nullable', 'string', 'max:100'],
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->with('cart_error', 'Your cart looks empty - please add at least one item.');
        }

        $data = $validator->validated();

        // Reuse the table's currently-open tab instead of starting a second
        // one, in case staff already opened it or an earlier scan did.
        $order = Order::where('table_id', $table->id)
            ->whereIn('status', ['open', 'sent'])
            ->latest()
            ->first();

        if (! $order) {
            $order = Order::create([
                'table_id' => $table->id,
                'type' => 'dine_in',
                'guest_count' => 1,
                'status' => 'open',
                'notes' => $data['guest_name'] ?? null,
            ]);

            $table->update(['status' => 'occupied']);
        }

        foreach ($data['items'] as $line) {
            $menuItem = MenuItem::findOrFail($line['menu_item_id']);

            $order->items()->create([
                'menu_item_id' => $menuItem->id,
                'quantity' => $line['quantity'],
                'unit_price' => $menuItem->base_price,
                'notes' => $line['notes'] ?? null,
                'status' => 'pending',
            ]);
        }

        return redirect()->route('order.menu', $table)
            ->with('status', 'Thanks! Your order has been sent to our staff for confirmation.');
    }
}
