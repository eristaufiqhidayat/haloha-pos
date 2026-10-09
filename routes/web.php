<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserAccessController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->middleware('throttle:login')->name('login.store');
});
Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/', function () {
        foreach (['dashboard' => 'dashboard', 'pos' => 'pos.index', 'products' => 'products.index', 'categories' => 'categories.index', 'stock' => 'stock.index', 'sales' => 'reports.sales', 'inventory' => 'reports.inventory', 'users' => 'users.index', 'roles' => 'roles.index', 'permissions' => 'permissions.index'] as $module => $route) {
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
    Route::get('/orders/{order}/edit', [PosController::class, 'index'])->middleware('permission:pos.create')->name('orders.edit');
    Route::put('/orders/{order}', [PosController::class, 'update'])->middleware('permission:pos.create')->name('orders.update');
    Route::get('/orders/{order}/payment', [PosController::class, 'payment'])->middleware('permission:pos.create')->name('orders.payment');
    Route::post('/orders/{order}/payment', [PosController::class, 'pay'])->middleware('permission:pos.create')->name('orders.pay');
    Route::get('/orders/{order}/kitchen', [PosController::class, 'kitchen'])->middleware('permission:pos.view')->name('orders.kitchen');
    Route::get('/users/{user}/access', [UserAccessController::class, 'edit'])->middleware('permission:users.update')->name('users.access');
    Route::put('/users/{user}/access', [UserAccessController::class, 'update'])->middleware('permission:users.update')->name('users.access.update');
    Route::get('/sales/{sale}', [PosController::class, 'show'])->name('sales.show');
    foreach (['products' => ProductController::class, 'users' => UserController::class, 'roles' => RoleController::class] as $resource => $controller) {
        Route::resource($resource, $controller)->except('show')->middlewareFor('index', 'permission:'.$resource.'.view')->middlewareFor(['create', 'store'], 'permission:'.$resource.'.create')->middlewareFor(['edit', 'update'], 'permission:'.$resource.'.update')->middlewareFor('destroy', 'permission:'.$resource.'.delete');
    }
    Route::get('/categories', [CategoryController::class, 'index'])->middleware('permission:categories.view')->name('categories.index');
    Route::post('/categories', [CategoryController::class, 'store'])->middleware('permission:categories.create')->name('categories.store');
    Route::put('/categories/{category}', [CategoryController::class, 'update'])->middleware('permission:categories.update')->name('categories.update');
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->middleware('permission:categories.delete')->name('categories.destroy');
    Route::get('/stock', [StockController::class, 'index'])->middleware('permission:stock.view')->name('stock.index');
    Route::post('/stock', [StockController::class, 'store'])->middleware('permission:stock.create')->name('stock.store');
    Route::get('/permissions', [PermissionController::class, 'index'])->middleware('permission:permissions.view')->name('permissions.index');
    Route::put('/permissions/{role}', [PermissionController::class, 'update'])->middleware('permission:permissions.update')->name('permissions.update');
    Route::get('/reports/sales', [ReportController::class, 'sales'])->middleware('permission:sales.view')->name('reports.sales');
    Route::get('/reports/sales/export', [ReportController::class, 'exportSales'])->middleware('permission:sales.export')->name('reports.sales.export');
    Route::get('/reports/inventory', [ReportController::class, 'inventory'])->middleware('permission:inventory.view')->name('reports.inventory');
    Route::get('/reports/inventory/export', [ReportController::class, 'exportInventory'])->middleware('permission:inventory.export')->name('reports.inventory.export');
});
