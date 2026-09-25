<?php

namespace Tests\Feature\Pos;

use App\Models\Branch;
use App\Models\Category;
use App\Models\DiningTable;
use App\Models\Floor;
use App\Models\MenuItem;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesStaff;
use Tests\TestCase;

/**
 * End-to-end coverage of the core POS loop: open an order, add an item,
 * send it to the kitchen, bump it through KDS to served, bill it, and pay
 * it off - the path every shift actually runs, exercised in one test so a
 * change anywhere in that chain (routes, controllers, BillingService,
 * StockService/OrderItemObserver) shows up here.
 */
class OrderFlowTest extends TestCase
{
    use CreatesStaff, RefreshDatabase;

    public function test_full_order_lifecycle_from_open_to_paid(): void
    {
        $waiter = $this->staff('waiter');

        $branch = Branch::create(['name' => 'Main Branch', 'tax_rate' => 10, 'currency' => 'USD', 'is_active' => true]);
        $floor = Floor::create(['branch_id' => $branch->id, 'name' => 'Ground Floor', 'sort_order' => 1]);
        $table = DiningTable::create(['floor_id' => $floor->id, 'label' => 'T1', 'seats' => 4, 'status' => 'free']);
        $category = Category::create(['name' => 'Mains', 'is_active' => true]);
        $menuItem = MenuItem::create([
            'category_id' => $category->id,
            'name' => 'Grilled Chicken',
            'base_price' => 12.50,
            'is_available' => true,
        ]);

        $this->actingAs($waiter);

        // 1. Open a dine-in order for the table.
        $orderResponse = $this->post('/pos/orders', [
            'table_id' => $table->id,
            'type' => 'dine_in',
            'guest_count' => 2,
        ]);
        $order = Order::firstOrFail();
        $orderResponse->assertRedirect(route('pos.orders.show', $order));
        $this->assertSame($waiter->id, $order->waiter_id);
        $this->assertSame('occupied', $table->fresh()->status);

        // 2. Add a line item - unit_price is a snapshot of the menu item's price.
        $this->post("/pos/orders/{$order->id}/items", [
            'menu_item_id' => $menuItem->id,
            'quantity' => 2,
        ])->assertRedirect(route('pos.orders.show', $order));

        $orderItem = $order->items()->firstOrFail();
        $this->assertEquals(12.50, (float) $orderItem->unit_price);
        $this->assertSame(2, $orderItem->quantity);

        // 3. Send the pending item(s) to the kitchen.
        $this->post("/pos/orders/{$order->id}/send-to-kitchen")->assertRedirect();
        $this->assertSame('sent', $orderItem->fresh()->status);
        $this->assertSame('sent', $order->fresh()->status);

        // 4. Bump it through the kitchen: sent -> preparing -> ready -> served
        // (route is PATCH, matching OrderItemController::updateStatus).
        $this->patch("/pos/order-items/{$orderItem->id}/status", ['status' => 'preparing'])->assertRedirect();
        $this->assertSame('preparing', $orderItem->fresh()->status);

        $this->patch("/pos/order-items/{$orderItem->id}/status", ['status' => 'ready'])->assertRedirect();
        $this->patch("/pos/order-items/{$orderItem->id}/status", ['status' => 'served'])->assertRedirect();
        $this->assertSame('served', $orderItem->fresh()->status);

        // 5. Bill the order - no discount/service charge, 10% tax from the branch default.
        $billResponse = $this->post("/pos/orders/{$order->id}/bills", []);
        $bill = $order->bills()->firstOrFail();
        $billResponse->assertRedirect(route('pos.bills.show', $bill));

        $this->assertEquals(25.00, (float) $bill->subtotal);   // 2 x 12.50
        $this->assertEquals(2.50, (float) $bill->tax_total);   // 10% of 25.00
        $this->assertEquals(27.50, (float) $bill->grand_total);
        $this->assertSame('unpaid', $bill->status);
        $this->assertSame('billed', $order->fresh()->status);

        // 6. Pay it off in full - status flips to paid once balanceDue() hits 0.
        $this->post("/pos/bills/{$bill->id}/payments", [
            'method' => 'cash',
            'amount' => 27.50,
        ])->assertRedirect();
        $this->assertSame('paid', $bill->fresh()->status);

        // 7. Close the order - table goes back to free since the bill is fully paid.
        $this->post("/pos/orders/{$order->id}/close")->assertRedirect(route('pos.orders.index'));
        $this->assertSame('closed', $order->fresh()->status);
        $this->assertSame('free', $table->fresh()->status);
    }

    public function test_an_order_cannot_be_closed_while_its_bill_is_unpaid(): void
    {
        $waiter = $this->staff('waiter');
        $category = Category::create(['name' => 'Mains', 'is_active' => true]);
        $menuItem = MenuItem::create(['category_id' => $category->id, 'name' => 'Soup', 'base_price' => 5, 'is_available' => true]);

        $this->actingAs($waiter);
        $this->post('/pos/orders', ['type' => 'takeaway', 'guest_count' => 1]);
        $order = Order::firstOrFail();

        $this->post("/pos/orders/{$order->id}/items", ['menu_item_id' => $menuItem->id, 'quantity' => 1]);
        $this->post("/pos/orders/{$order->id}/bills", []);

        $response = $this->post("/pos/orders/{$order->id}/close");

        $response->assertSessionHasErrors('order');
        $this->assertNotSame('closed', $order->fresh()->status);
    }
}
