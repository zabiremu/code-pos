<?php

namespace App\Http\Controllers\POS;

use App\Events\OrderItemStatusUpdated;
use App\Http\Controllers\Controller;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OrderItemController extends Controller
{
    /** Add one line item to an open order. */
    public function store(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'menu_item_id' => ['required', 'exists:menu_items,id'],
            'item_variant_id' => ['nullable', 'exists:item_variants,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:255'],
            'modifiers' => ['array'],
            'modifiers.*' => ['exists:modifiers,id'],
        ]);

        $menuItem = MenuItem::findOrFail($data['menu_item_id']);
        $variantDelta = $data['item_variant_id']
            ? $menuItem->variants()->whereKey($data['item_variant_id'])->value('price_delta')
            : 0;

        $orderItem = $order->items()->create([
            'menu_item_id' => $menuItem->id,
            'item_variant_id' => $data['item_variant_id'] ?? null,
            'quantity' => $data['quantity'],
            'unit_price' => $menuItem->base_price + $variantDelta,
            'notes' => $data['notes'] ?? null,
            'status' => 'pending',
        ]);

        if (! empty($data['modifiers'])) {
            $priceDeltas = \App\Models\Modifier::whereIn('id', $data['modifiers'])->pluck('price_delta', 'id');
            foreach ($data['modifiers'] as $modifierId) {
                $orderItem->modifiers()->attach($modifierId, ['price_delta' => $priceDeltas[$modifierId] ?? 0]);
            }
        }

        return redirect()->route('pos.orders.show', $order)->with('status', 'Item added.');
    }

    /** Fires every pending item on the order to the kitchen at once. */
    public function sendToKitchen(Order $order): RedirectResponse
    {
        $items = $order->items()->where('status', 'pending')->get();

        foreach ($items as $item) {
            $item->update(['status' => 'sent']);
            broadcast(new OrderItemStatusUpdated($item))->toOthers();
        }

        $order->update(['status' => 'sent']);

        return back()->with('status', "{$items->count()} item(s) sent to the kitchen.");
    }

    public function updateStatus(Request $request, OrderItem $orderItem): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:pending,sent,preparing,ready,served,cancelled'],
        ]);

        $orderItem->update($data);
        broadcast(new OrderItemStatusUpdated($orderItem))->toOthers();

        return back()->with('status', 'Item status updated.');
    }
}
