<?php

use App\Http\Controllers\Install\InstallController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Installer Routes
|--------------------------------------------------------------------------
| Registered in bootstrap/app.php:
|   ->withRouting(..., then: function () {
|       Route::middleware('web')->group(base_path('routes/install.php'));
|   })
| Every route here also needs the 'redirect.if.installed' middleware alias
| (App\Http\Middleware\RedirectIfInstalled) registered and applied — see README.
*/

Route::prefix('install')->name('install.')->middleware('redirect.if.installed')->group(function () {
    Route::get('/', [InstallController::class, 'welcome'])->name('welcome');
    Route::get('/requirements', [InstallController::class, 'requirements'])->name('requirements');
    Route::get('/purchase-code', [InstallController::class, 'showPurchaseCode'])->name('purchase-code');
    Route::post('/purchase-code', [InstallController::class, 'verifyPurchaseCode'])->name('purchase-code.verify');
    Route::get('/database', [InstallController::class, 'showDatabase'])->name('database');
    Route::post('/database', [InstallController::class, 'storeDatabase'])->name('database.store');
    Route::get('/finish', [InstallController::class, 'finish'])->name('finish');
});
