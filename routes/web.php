<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\SupplierController;
use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\GrnController;
use App\Http\Controllers\Admin\GrnReturnController;
use App\Http\Controllers\Admin\PurchaseController;
use App\Http\Controllers\Admin\LabelController;
use App\Http\Controllers\Admin\ProductCsvController;
use App\Http\Controllers\Admin\ExpenseCategoryController;
use App\Http\Controllers\Admin\ExpenseController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\CustomerReceiptController;
use App\Http\Controllers\Admin\SaleReturnController;
use App\Http\Controllers\Admin\StockAdjustmentController;
use App\Http\Controllers\Admin\StockTransferController;
use App\Http\Controllers\Admin\SupplierPaymentController;
use App\Http\Controllers\Admin\UnitController;
use App\Http\Controllers\Admin\WarehouseController;
use App\Http\Controllers\POS\BillController;
use App\Http\Controllers\POS\PaymentController;
use App\Http\Controllers\POS\RegisterController;
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

// Signed in: your home for your role (see App\Support\HomeRoute). Otherwise the login page.
// This used to always go to login, which bounced signed-in users back to "/" forever.
Route::get('/', fn () => redirect(\App\Support\HomeRoute::for(auth()->user())))->name('home');

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

            // Before the resource so "export"/"import" aren't read as a product id.
            Route::get('products/export', [ProductCsvController::class, 'export'])->name('products.export');
            Route::get('products/import', [ProductCsvController::class, 'create'])->name('products.import');
            Route::post('products/import', [ProductCsvController::class, 'store'])->middleware('throttle:10,1')->name('products.import.store');
            Route::get('products/import/template', [ProductCsvController::class, 'template'])->name('products.import.template');
            Route::resource('products', ProductController::class)->except('show');

            Route::get('labels', [LabelController::class, 'index'])->name('labels.index');
            Route::post('labels/print', [LabelController::class, 'print'])->name('labels.print');
            Route::post('labels/generate-skus', [LabelController::class, 'generateSkus'])->name('labels.generate-skus');
            Route::resource('units', UnitController::class)->except('show');
            Route::resource('brands', BrandController::class)->except('show');
            Route::resource('warehouses', WarehouseController::class)->except('show');

            // Purchasing: purchase orders -> goods received (GRN) -> returns to supplier.
            Route::resource('purchases', PurchaseController::class);
            Route::patch('purchases/{purchase}/cancel', [PurchaseController::class, 'cancel'])->name('purchases.cancel');
            Route::resource('grns', GrnController::class);
            Route::resource('grn-returns', GrnReturnController::class)->parameters(['grn-returns' => 'grnReturn']);

            Route::resource('suppliers', SupplierController::class);
            Route::resource('supplier-payments', SupplierPaymentController::class)->except('show')->parameters(['supplier-payments' => 'supplierPayment']);

            Route::resource('customers', CustomerController::class);
            Route::resource('customer-receipts', CustomerReceiptController::class)->only(['index', 'create', 'store', 'show', 'destroy'])->parameters(['customer-receipts' => 'customerReceipt']);
            Route::resource('sale-returns', SaleReturnController::class)->only(['index', 'create', 'store', 'show', 'destroy'])->parameters(['sale-returns' => 'saleReturn']);

            Route::resource('stock-transfers', StockTransferController::class)->parameters(['stock-transfers' => 'stockTransfer']);
            Route::resource('stock-adjustments', StockAdjustmentController::class)->parameters(['stock-adjustments' => 'stockAdjustment']);

            Route::get('/staff', [StaffController::class, 'index'])->name('staff.index');
            Route::post('/staff', [StaffController::class, 'store'])->name('staff.store');
            Route::get('/staff/{staffMember}', [StaffController::class, 'show'])->name('staff.show');
            Route::put('/staff/{staffMember}', [StaffController::class, 'update'])->name('staff.update');
            Route::delete('/staff/{staffMember}', [StaffController::class, 'destroy'])->name('staff.destroy');

            Route::get('/shifts', [AdminShiftController::class, 'index'])->name('shifts.index');

            Route::get('/reports/sales', [ReportController::class, 'sales'])->name('reports.sales');
            Route::get('/reports/low-stock', [ReportController::class, 'lowStock'])->name('reports.low-stock');
            Route::get('/reports/profit-loss', [ReportController::class, 'profitLoss'])->name('reports.profit-loss');
            Route::get('/reports/stock-value', [ReportController::class, 'stockValue'])->name('reports.stock-value');
            Route::get('/reports/purchases', [ReportController::class, 'purchases'])->name('reports.purchases');

            Route::resource('expenses', ExpenseController::class)->except('show');
            Route::resource('expense-categories', ExpenseCategoryController::class)->only(['index', 'store', 'update', 'destroy'])->parameters(['expense-categories' => 'expenseCategory']);

            // Holds the SMTP password, so admins only - not managers.
            Route::middleware('role:admin')->group(function () {
                Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
                Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
                Route::post('/settings/test-email', [SettingsController::class, 'sendTestEmail'])
                    ->middleware('throttle:5,1')->name('settings.test-email');
                Route::post('/settings/license/deactivate', [SettingsController::class, 'deactivateLicense'])
                    ->middleware('throttle:5,1')->name('settings.license.deactivate');
            });
        });

    Route::middleware(['role:admin|manager|cashier'])
        ->prefix('pos')
        ->name('pos.')
        ->group(function () {
            Route::get('/register', [RegisterController::class, 'index'])->name('register');
            Route::post('/register/checkout', [RegisterController::class, 'checkout'])->middleware('throttle:60,1')->name('register.checkout');
            Route::get('/register/receipt/{bill}', [RegisterController::class, 'receipt'])->name('register.receipt');
            Route::post('/register/customers', [RegisterController::class, 'storeCustomer'])->middleware('throttle:30,1')->name('register.customers.store');

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
