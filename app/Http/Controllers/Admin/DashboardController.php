<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    /** Today's headline numbers for the admin landing page. */
    public function __invoke(): View
    {
        $todayOrders = Order::whereDate('created_at', today())->count();
        $openOrders = Order::whereIn('status', ['open', 'sent', 'served'])->count();

        return view('admin.dashboard', compact('todayOrders', 'openOrders'));
    }
}
