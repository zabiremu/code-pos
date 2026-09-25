<?php

namespace App\Events;

use App\Models\OrderItem;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast on a per-kitchen-station channel so each KDS screen only
 * receives tickets for its own station. Falls back to nothing special if
 * broadcasting isn't configured — the KDS view polls as a fallback
 * (see resources/views/kds/board.blade.php).
 */
class OrderItemStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public OrderItem $orderItem)
    {
    }

    public function broadcastOn(): array
    {
        $station = $this->orderItem->kitchen_station ?: 'general';

        return [new Channel("kitchen-station.{$station}")];
    }

    public function broadcastAs(): string
    {
        return 'order-item.status-updated';
    }

    public function broadcastWith(): array
    {
        return [
            'order_item_id' => $this->orderItem->id,
            'order_id' => $this->orderItem->order_id,
            'status' => $this->orderItem->status,
            'menu_item_name' => $this->orderItem->menuItem->name,
            'quantity' => $this->orderItem->quantity,
            'notes' => $this->orderItem->notes,
        ];
    }
}
