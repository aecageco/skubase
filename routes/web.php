<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

//Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
// routes/web.php

Route::post('/item/approve/{sku}', [App\Http\Controllers\ItemApprovalController::class, 'approve'])
    ->name('item.approve');

// routes/web.php
Route::post('/items/reject/{sku}', [App\Http\Controllers\ItemApprovalController::class, 'reject'])
    ->name('itemapproval.reject');
Route::get('/items/approved', [App\Http\Controllers\ItemsController::class, 'approved'])
    ->name('items.approved');
Route::get('/items/rejected', [App\Http\Controllers\ItemsController::class, 'rejected'])
    ->name('items.rejected');
Route::get('/items/missing', [App\Http\Controllers\ItemsController::class, 'missing'])
    ->name('items.missing');
Route::resource('items','App\Http\Controllers\ItemsController');

Route::resource('content','App\Http\Controllers\ContentController');
use App\Http\Controllers\ItemApprovalController;


