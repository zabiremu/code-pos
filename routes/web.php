<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FloorController;
use App\Http\Controllers\Admin\MenuItemController;
use App\Http\Controllers\Admin\ModifierGroupController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\TableController;
use App\Http\Controllers\KDS\TicketController;
use App\Http\Controllers\POS\BillController;
use App\Http\Controllers\POS\OrderController;
use App\Http\Controllers\POS\OrderItemController;
use App\Http\Controllers\POS\PaymentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
| Login/logout live in routes/auth.php (hand-built — staff accounts are
| admin-created via Admin\StaffController, there's no self-registration).
| Role names match App\Enums\Role. The installer lives in
| routes/install.php. All three are registered in bootstrap/app.php.
*/

Route::get('/', fn () => redirect()->route('login'));

Route::middleware(['auth'])->group(function () {

    Route::middleware(['role:admin|manager'])
        ->prefix('admin')
        ->name('admin.')
        ->group(function () {
            Route::get('/dashboard', DashboardController::class)->name('dashboard');

            Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
            Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
            Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
            Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

            Route::get('/menu-items', [MenuItemController::class, 'index'])->name('menu-items.index');
            Route::get('/menu-items/create', [MenuItemController::class, 'create'])->name('menu-items.create');
            Route::post('/menu-items', [MenuItemController::class, 'store'])->name('menu-items.store');
            Route::put('/menu-items/{menuItem}', [MenuItemController::class, 'update'])->name('menu-items.update');
            Route::delete('/menu-items/{menuItem}', [MenuItemController::class, 'destroy'])->name('menu-items.destroy');

            Route::get('/modifier-groups', [ModifierGroupController::class, 'index'])->name('modifier-groups.index');
            Route::post('/modifier-groups', [ModifierGroupController::class, 'store'])->name('modifier-groups.store');
            Route::delete('/modifier-groups/{modifierGroup}', [ModifierGroupController::class, 'destroy'])->name('modifier-groups.destroy');

            Route::get('/floors', [FloorController::class, 'index'])->name('floors.index');
            Route::post('/floors', [FloorController::class, 'store'])->name('floors.store');
            Route::delete('/floors/{floor}', [FloorController::class, 'destroy'])->name('floors.destroy');

            Route::get('/tables', [TableController::class, 'index'])->name('tables.index');
            Route::post('/tables', [TableController::class, 'store'])->name('tables.store');
            Route::patch('/tables/{table}/status', [TableController::class, 'updateStatus'])->name('tables.status');
            Route::delete('/tables/{table}', [TableController::class, 'destroy'])->name('tables.destroy');

            Route::get('/staff', [StaffController::class, 'index'])->name('staff.index');
            Route::post('/staff', [StaffController::class, 'store'])->name('staff.store');
            Route::put('/staff/{staffMember}', [StaffController::class, 'update'])->name('staff.update');
            Route::delete('/staff/{staffMember}', [StaffController::class, 'destroy'])->name('staff.destroy');

            Route::get('/reports/sales', [ReportController::class, 'sales'])->name('reports.sales');
            Route::get('/reports/low-stock', [ReportController::class, 'lowStock'])->name('reports.low-stock');
        });

    Route::middleware(['role:admin|manager|waiter|cashier'])
        ->prefix('pos')
        ->name('pos.')
        ->group(function () {
            Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
            Route::get('/orders/create', [OrderController::class, 'create'])->name('orders.create');
            Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');
            Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
            Route::post('/orders/{order}/close', [OrderController::class, 'close'])->name('orders.close');

            Route::post('/orders/{order}/items', [OrderItemController::class, 'store'])->name('orders.items.store');
            Route::post('/orders/{order}/send-to-kitchen', [OrderItemController::class, 'sendToKitchen'])->name('orders.send-to-kitchen');
            Route::patch('/order-items/{orderItem}/status', [OrderItemController::class, 'updateStatus'])->name('order-items.status');

            Route::post('/orders/{order}/bills', [BillController::class, 'store'])->name('bills.store');
            Route::get('/bills/{bill}', [BillController::class, 'show'])->name('bills.show');
            Route::post('/bills/{bill}/payments', [PaymentController::class, 'store'])->name('bills.payments.store');
        });

    Route::middleware(['role:admin|manager|kitchen'])
        ->prefix('kds')
        ->name('kds.')
        ->group(function () {
            Route::get('/tickets', [TicketController::class, 'index'])->name('tickets.index');
            Route::post('/tickets/{orderItem}/bump', [TicketController::class, 'bump'])->name('tickets.bump');
        });
});
