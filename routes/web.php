<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\POS\OrderController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
| Auth routes (login/register/password reset) are added by Breeze —
| see README setup steps. Role names match App\Enums\Role.
*/

Route::get('/', fn () => redirect()->route('login'));

Route::middleware(['auth'])->group(function () {

    Route::middleware(['role:admin,manager'])
        ->prefix('admin')
        ->name('admin.')
        ->group(function () {
            Route::get('/dashboard', DashboardController::class)->name('dashboard');
            // Menu, tables, staff, reports controllers land in the feature-modules phase.
        });

    Route::middleware(['role:admin,manager,waiter'])
        ->prefix('pos')
        ->name('pos.')
        ->group(function () {
            Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
            Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');
            Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
        });

    Route::middleware(['role:admin,manager,kitchen'])
        ->prefix('kds')
        ->name('kds.')
        ->group(function () {
            // Kitchen Display System routes — added with the real-time order-item
            // status broadcasting in the order-taking + KDS phase.
        });
});
