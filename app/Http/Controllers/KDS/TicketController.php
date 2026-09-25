<?php

namespace App\Http\Controllers\KDS;

use App\Events\OrderItemStatusUpdated;
use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Kitchen Display System board. Groups sent/preparing order items by
 * station so each kitchen screen can filter to its own tickets client-side
 * (?station=grill). Polls every few seconds as a fallback when no
 * broadcast connection is configured — see resources/views/kds/board.blade.php.
 */
class TicketController extends Controller
{
    public function index(Request $request): View
    {
        $station = $request->query('station');

        $items = OrderItem::with(['order.table', 'menuItem', 'modifiers'])
            ->whereIn('status', ['sent', 'preparing'])
            ->when($station, fn ($q) => $q->where('kitchen_station', $station))
            ->orderBy('created_at')
            ->get();

        return view('kds.board', compact('items', 'station'));
    }

    /** Bump a ticket forward: sent -> preparing -> ready -> served. */
    public function bump(OrderItem $orderItem): RedirectResponse
    {
        $next = match ($orderItem->status) {
            'sent' => 'preparing',
            'preparing' => 'ready',
            'ready' => 'served',
            default => $orderItem->status,
        };

        $orderItem->update(['status' => $next]);
        broadcast(new OrderItemStatusUpdated($orderItem))->toOthers();

        return back();
    }
}
