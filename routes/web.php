<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\POS\BillController;
use App\Http\Controllers\POS\PaymentController;
use App\Http\Controllers\POS\SaleController;
use App\Http\Controllers\POS\SaleItemController;
use App\Http\Controllers\Admin\ShiftController as AdminShiftController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ShiftController;
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

    // Self-service profile/password editing - any authenticated role, not
    // gated behind admin|manager like Admin\StaffController is.
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'edit'])->name('edit');
        Route::put('/', [ProfileController::class, 'update'])->name('update');
        Route::put('/password', [ProfileController::class, 'updatePassword'])->name('password');
    });

    // Shift clock-in/out - any authenticated role (part of Employee
    // Management). Admin\ShiftController below (admin|manager only) is the
    // attendance report over everyone's shifts.
    Route::prefix('shifts')->name('shifts.')->group(function () {
        Route::post('/clock-in', [ShiftController::class, 'clockIn'])->name('clock-in');
        Route::post('/clock-out', [ShiftController::class, 'clockOut'])->name('clock-out');
    });

    Route::middleware(['role:admin|manager'])
        ->prefix('admin')
        ->name('admin.')
        ->group(function () {
            Route::get('/dashboard', DashboardController::class)->name('dashboard');

            Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
            Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
            Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
            Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

            Route::get('/products', [ProductController::class, 'index'])->name('products.index');
            Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
            Route::post('/products', [ProductController::class, 'store'])->name('products.store');
            Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update');
            Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');

            Route::get('/staff', [StaffController::class, 'index'])->name('staff.index');
            Route::post('/staff', [StaffController::class, 'store'])->name('staff.store');
            Route::get('/staff/{staffMember}', [StaffController::class, 'show'])->name('staff.show');
            Route::put('/staff/{staffMember}', [StaffController::class, 'update'])->name('staff.update');
            Route::delete('/staff/{staffMember}', [StaffController::class, 'destroy'])->name('staff.destroy');

            Route::get('/shifts', [AdminShiftController::class, 'index'])->name('shifts.index');

            Route::get('/reports/sales', [ReportController::class, 'sales'])->name('reports.sales');
            Route::get('/reports/low-stock', [ReportController::class, 'lowStock'])->name('reports.low-stock');

            // Holds the SMTP password, so admins only - not managers.
            Route::middleware('role:admin')->group(function () {
                Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
                Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
                Route::post('/settings/test-email', [SettingsController::class, 'sendTestEmail'])
                    ->middleware('throttle:5,1')->name('settings.test-email');
            });
        });

    Route::middleware(['role:admin|manager|cashier'])
        ->prefix('pos')
        ->name('pos.')
        ->group(function () {
            Route::get('/sales', [SaleController::class, 'index'])->name('sales.index');
            Route::post('/sales', [SaleController::class, 'store'])->name('sales.store');
            Route::get('/sales/{sale}', [SaleController::class, 'show'])->name('sales.show');
            Route::post('/sales/{sale}/close', [SaleController::class, 'close'])->name('sales.close');

            Route::post('/sales/{sale}/items', [SaleItemController::class, 'store'])->name('sales.items.store');
            Route::delete('/sale-items/{saleItem}', [SaleItemController::class, 'destroy'])->name('sale-items.destroy');

            Route::post('/sales/{sale}/bills', [BillController::class, 'store'])->name('bills.store');
            Route::get('/bills/{bill}', [BillController::class, 'show'])->name('bills.show');
            Route::post('/bills/{bill}/payments', [PaymentController::class, 'store'])->name('bills.payments.store');
        });
});
