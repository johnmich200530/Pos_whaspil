<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\SettingsController;

// PIN / Username Login
Route::get('/',  [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Authenticated routes
Route::middleware('auth.pos')->group(function () {

    // Dashboard — all roles
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/stats', [DashboardController::class, 'stats'])->name('dashboard.stats');

    // Active orders JSON for polling
    Route::get('/orders/poll', [OrderController::class, 'poll'])->name('orders.poll');

    // Table Status (cashier view) — cashier, manager
    Route::middleware('pos.role:cashier,manager')->group(function () {
        Route::get('/tables', [OrderController::class, 'tables'])->name('tables.index');
    });

    // New Order (assign table, take order) — waiter, manager only
    Route::middleware('pos.role:waiter,manager')->group(function () {
        Route::get('/orders/new',  [OrderController::class, 'create'])->name('orders.create');
        Route::post('/orders',     [OrderController::class, 'store'])->name('orders.store');
    });

    // Active Orders & order management — all staff
    Route::get('/orders/active',             [OrderController::class, 'active'])->name('orders.active');
    Route::get('/orders/poll',               [OrderController::class, 'poll'])->name('orders.poll');
    Route::get('/orders/{order}',            [OrderController::class, 'show'])->name('orders.show');
    Route::get('/orders/{order}/status-json',[OrderController::class, 'statusJson'])->name('orders.status-json');
    Route::patch('/orders/{order}/status',   [OrderController::class, 'updateStatus'])->name('orders.status');
    Route::delete('/orders/{order}',         [OrderController::class, 'destroy'])->name('orders.destroy');

    // Edit order items — waiter, manager
    Route::middleware('pos.role:waiter,manager')->group(function () {
        Route::patch('/order-items/{orderItem}',  [OrderController::class, 'updateItem'])->name('order-items.update');
        Route::delete('/order-items/{orderItem}', [OrderController::class, 'destroyItem'])->name('order-items.destroy');
    });

    // Payment & Receipt — cashier, manager
    Route::middleware('pos.role:cashier,manager')->group(function () {
        Route::post('/payments',         [PaymentController::class, 'store'])->name('payments.store');
        Route::get('/receipt/{receipt}', [ReceiptController::class, 'show'])->name('receipt.show');
        Route::post('/settings/gcash-qr', [SettingsController::class, 'updateGcashQr'])->name('settings.gcash-qr');
    });

    // Inventory — chef, manager
    Route::middleware('pos.role:chef,manager')->group(function () {
        Route::get('/inventory',                [InventoryController::class, 'index'])->name('inventory.index');
        Route::post('/inventory',               [InventoryController::class, 'store'])->name('inventory.store');
        Route::patch('/inventory/{inventory}',  [InventoryController::class, 'update'])->name('inventory.update');
        Route::delete('/inventory/{inventory}', [InventoryController::class, 'destroy'])->name('inventory.destroy');
    });

    // Menu Management — manager only
    Route::middleware('pos.role:manager')->group(function () {
        Route::get('/menu',           [MenuController::class, 'index'])->name('menu.index');
        Route::post('/menu',          [MenuController::class, 'store'])->name('menu.store');
        Route::patch('/menu/{menu}',  [MenuController::class, 'update'])->name('menu.update');
        Route::delete('/menu/{menu}', [MenuController::class, 'destroy'])->name('menu.destroy');
    });

    // Sales Reports — manager only
    Route::middleware('pos.role:manager')->group(function () {
        Route::get('/sales',        [SalesController::class, 'index'])->name('sales.index');
        Route::get('/sales/export', [SalesController::class, 'export'])->name('sales.export');
    });

    // Employees — manager only
    Route::middleware('pos.role:manager')->group(function () {
        Route::get('/employees',                    [EmployeeController::class, 'index'])->name('employees.index');
        Route::post('/employees',                   [EmployeeController::class, 'store'])->name('employees.store');
        Route::patch('/employees/{employee}',       [EmployeeController::class, 'update'])->name('employees.update');
        Route::delete('/employees/{employee}',      [EmployeeController::class, 'destroy'])->name('employees.destroy');
    });

    // Audit Log — manager only
    Route::middleware('pos.role:manager')->group(function () {
        Route::get('/audit', [AuditController::class, 'index'])->name('audit.index');
    });

});
