<?php

namespace App\Providers;

use App\Models\OrderItem;
use App\Observers\OrderItemObserver;
use Illuminate\Support\ServiceProvider;

/**
 * The Laravel skeleton ships its own near-empty AppServiceProvider; this
 * version adds the one thing the POS needs registered app-wide: ingredient
 * stock deduction when an order item is served. Merge this in rather than
 * overwrite if the skeleton's copy has other registrations by the time you
 * scaffold (see README setup steps).
 */
class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        OrderItem::observe(OrderItemObserver::class);
    }
}
