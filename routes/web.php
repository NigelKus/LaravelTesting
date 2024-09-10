<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\SalesOrderController;
use App\Http\Controllers\SalesInvoiceController;

Route::get('/', function () {
    return view('auth/login');
});

Route::get('customer', function () {
    return view('customer');
});

Auth::routes();

//Customer routing
Route::prefix('admin/master/customer')
    ->name('customer.')
    ->group(function () {
        Route::get('index', [CustomerController::class, 'index'])->name('index');
        Route::get('create', [CustomerController::class, 'create'])->name('create');
        Route::post('store', [CustomerController::class, 'store'])->name('store');
        Route::get('{id}', [CustomerController::class, 'show'])->name('show');
        Route::get('{id}/edit', [CustomerController::class, 'edit'])->name('edit');
        Route::put('{id}', [CustomerController::class, 'update'])->name('update');
        Route::delete('{id}', [CustomerController::class, 'destroy'])->name('destroy');
        Route::post('{id}/update-status', [CustomerController::class, 'updateStatus'])->name('updateStatus');
    });

//Product Routing
Route::prefix('admin/master/product')
    ->name('product.')
    ->group(function () {
        Route::get('index', [ProductController::class, 'index'])->name('index');
        Route::get('create', [ProductController::class, 'create'])->name('create');
        Route::post('store', [ProductController::class, 'store'])->name('store');
        Route::get('{id}', [ProductController::class, 'show'])->name('show');
        Route::get('{id}/edit', [ProductController::class, 'edit'])->name('edit');
        Route::put('{id}', [ProductController::class, 'update'])->name('update');
        Route::delete('{id}', [ProductController::class, 'destroy'])->name('destroy');
        Route::post('{id}/update-status', [ProductController::class, 'updateStatus'])->name('updateStatus');
    });

//Sales Order Routing
Route::prefix('admin/master/sales_order')
->name('sales_order.')
->group(function () {
    Route::get('index', [SalesOrderController::class, 'index'])->name('index');
    Route::get('create-copy', [SalesOrderController::class, 'create'])->name('create-copy');
    Route::post('store', [SalesOrderController::class, 'store'])->name('store');
    Route::get('{id}/edit', [SalesOrderController::class, 'edit'])->name('edit');
    Route::put('{id}', [SalesOrderController::class, 'update'])->name('update');
    Route::get('{id}', [SalesOrderController::class, 'show'])->name('show');
    Route::patch('{id}/update-status', [SalesOrderController::class, 'updateStatus'])->name('update_status');
// routes/web.php
Route::get('{id}/products', [SalesOrderController::class, 'getProducts'])->name('products');

});

//Purchase Routing
Route::prefix('admin/master/purchase')
    ->name('purchase.')
    ->group(function () {
        Route::get('index', [UserController::class, 'index'])->name('index');
        Route::get('create', [UserController::class, 'create'])->name('create');
});


//User Routing
Route::prefix('admin/master/user')
    ->name('user.')
    ->group(function () {
        Route::get('index', [UserController::class, 'index'])->name('index');
        Route::get('create', [UserController::class, 'create'])->name('create');
});

//SalesInvoice Routing
Route::prefix('admin/transactional/sales_invoice')
    ->name('sales_invoice.')
    ->group(function () {
        Route::get('index', [SalesInvoiceController::class, 'index'])->name('index');
        Route::get('create', [SalesInvoiceController::class, 'create'])->name('create');
        Route::post('store', [SalesInvoiceController::class, 'store'])->name('store');
        Route::get('{id}', [SalesInvoiceController::class, 'show'])->name('show');
        Route::patch('{id}/update-status', [SalesInvoiceController::class, 'updateStatus'])->name('update_status');
        Route::get('{id}/edit', [SalesInvoiceController::class, 'edit'])->name('edit');
        Route::put('{id}', [SalesInvoiceController::class, 'update'])->name('update');
});

//PaymentInvoice Routing
Route::prefix('admin/transactional/payment_invoice')
    ->name('payment_invoice.')
    ->group(function () {
        Route::get('index', [UserController::class, 'index'])->name('index');
        Route::get('create', [UserController::class, 'create'])->name('create');
});

//Home routing
Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

