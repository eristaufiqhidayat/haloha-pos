<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DiningTableController;
use App\Http\Controllers\GuestOrderController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserAccessController;
use Illuminate\Support\Facades\Route;

// Halaman tamu dari QR meja (tanpa login).
Route::prefix('m/{token}')->where(['token' => '[A-Za-z0-9]{16}'])->group(function () {
    Route::get('/', [GuestOrderController::class, 'menu'])->name('qr.menu');
    Route::post('/', [GuestOrderController::class, 'store'])->middleware('throttle:qr-order')->name('qr.store');
    Route::get('/pesanan/{order}', [GuestOrderController::class, 'show'])->name('qr.order');
    Route::post('/pesanan/{order}/bayar', [GuestOrderController::class, 'claim'])->middleware('throttle:qr-order')->name('qr.claim');
});
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->middleware('throttle:login')->name('login.store');
});
Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/', function () {
        foreach (['dashboard' => 'dashboard', 'pos' => 'pos.index', 'products' => 'products.index', 'categories' => 'categories.index', 'stock' => 'stock.index', 'tables' => 'tables.index', 'sales' => 'reports.sales', 'inventory' => 'reports.inventory', 'users' => 'users.index', 'roles' => 'roles.index', 'permissions' => 'permissions.index'] as $module => $route) {
            if (auth()->user()->hasPermission($module.'.view')) {
                return redirect()->route($route);
            }
        }

return view('no-access');
    })->name('home');
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->middleware('permission:dashboard.view')->name('dashboard');
    Route::get('/pos', [PosController::class, 'index'])->middleware('permission:pos.view')->name('pos.index');
    Route::post('/pos', [PosController::class, 'store'])->middleware('permission:pos.create')->name('pos.store');
    Route::get('/orders', [PosController::class, 'orders'])->middleware('permission:pos.view')->name('orders.index');
    Route::post('/orders/{order}/accept', [PosController::class, 'acceptGuest'])->middleware('permission:pos.create')->name('orders.accept');
    Route::post('/orders/{order}/reject', [PosController::class, 'rejectGuest'])->middleware('permission:pos.create')->name('orders.reject');
    Route::get('/orders/{order}/edit', [PosController::class, 'index'])->middleware('permission:pos.create')->name('orders.edit');
    Route::put('/orders/{order}', [PosController::class, 'update'])->middleware('permission:pos.create')->name('orders.update');
    Route::get('/orders/{order}/payment', [PosController::class, 'payment'])->middleware('permission:pos.create')->name('orders.payment');
    Route::post('/orders/{order}/payment', [PosController::class, 'pay'])->middleware('permission:pos.create')->name('orders.pay');
    Route::get('/orders/{order}/split', [PosController::class, 'splitForm'])->middleware('permission:pos.create')->name('orders.split');
    Route::post('/orders/{order}/split', [PosController::class, 'storeSplit'])->middleware('permission:pos.create')->name('orders.split.store');
    Route::delete('/orders/{order}/split', [PosController::class, 'destroySplit'])->middleware('permission:pos.create')->name('orders.split.destroy');
    Route::get('/orders/{order}/bills', [PosController::class, 'bills'])->middleware('permission:pos.view')->name('orders.bills');
    Route::get('/orders/{order}/bills/{number}/payment', [PosController::class, 'billPayment'])->middleware('permission:pos.create')->whereNumber('number')->name('orders.bills.payment');
    Route::post('/orders/{order}/bills/{number}/payment', [PosController::class, 'payBill'])->middleware('permission:pos.create')->whereNumber('number')->name('orders.bills.pay');
    Route::get('/orders/{order}/kitchen/{station?}', [PosController::class, 'kitchen'])->middleware('permission:pos.view')->name('orders.kitchen');
    Route::get('/users/{user}/access', [UserAccessController::class, 'edit'])->middleware('permission:users.update')->name('users.access');
    Route::put('/users/{user}/access', [UserAccessController::class, 'update'])->middleware('permission:users.update')->name('users.access.update');
    Route::get('/sales/{sale}', [PosController::class, 'show'])->name('sales.show');
    Route::get('/sales/{sale}/bills/{number}', [PosController::class, 'billReceipt'])->whereNumber('number')->name('sales.bill');
    foreach (['products' => ProductController::class, 'users' => UserController::class, 'roles' => RoleController::class] as $resource => $controller) {
        Route::resource($resource, $controller)->except('show')->middlewareFor('index', 'permission:'.$resource.'.view')->middlewareFor(['create', 'store'], 'permission:'.$resource.'.create')->middlewareFor(['edit', 'update'], 'permission:'.$resource.'.update')->middlewareFor('destroy', 'permission:'.$resource.'.delete');
    }
    Route::get('/categories', [CategoryController::class, 'index'])->middleware('permission:categories.view')->name('categories.index');
    Route::post('/categories', [CategoryController::class, 'store'])->middleware('permission:categories.create')->name('categories.store');
    Route::put('/categories/{category}', [CategoryController::class, 'update'])->middleware('permission:categories.update')->name('categories.update');
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->middleware('permission:categories.delete')->name('categories.destroy');
    Route::get('/tables', [DiningTableController::class, 'index'])->middleware('permission:tables.view')->name('tables.index');
    Route::get('/tables/print', [DiningTableController::class, 'print'])->middleware('permission:tables.view')->name('tables.print');
    Route::post('/tables', [DiningTableController::class, 'store'])->middleware('permission:tables.create')->name('tables.store');
    Route::put('/tables/qris', [DiningTableController::class, 'updateQris'])->middleware('permission:tables.update')->name('tables.qris');
    Route::put('/tables/{table}', [DiningTableController::class, 'update'])->middleware('permission:tables.update')->name('tables.update');
    Route::post('/tables/{table}/token', [DiningTableController::class, 'regenerate'])->middleware('permission:tables.update')->name('tables.token');
    Route::delete('/tables/{table}', [DiningTableController::class, 'destroy'])->middleware('permission:tables.delete')->name('tables.destroy');
    Route::get('/stock', [StockController::class, 'index'])->middleware('permission:stock.view')->name('stock.index');
    Route::post('/stock', [StockController::class, 'store'])->middleware('permission:stock.create')->name('stock.store');
    Route::get('/permissions', [PermissionController::class, 'index'])->middleware('permission:permissions.view')->name('permissions.index');
    Route::put('/permissions/{role}', [PermissionController::class, 'update'])->middleware('permission:permissions.update')->name('permissions.update');
    Route::get('/reports/sales', [ReportController::class, 'sales'])->middleware('permission:sales.view')->name('reports.sales');
    Route::get('/reports/sales/export', [ReportController::class, 'exportSales'])->middleware('permission:sales.export')->name('reports.sales.export');
    Route::get('/reports/inventory', [ReportController::class, 'inventory'])->middleware('permission:inventory.view')->name('reports.inventory');
    Route::get('/reports/inventory/export', [ReportController::class, 'exportInventory'])->middleware('permission:inventory.export')->name('reports.inventory.export');
});
