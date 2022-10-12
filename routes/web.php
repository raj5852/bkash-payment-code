<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BkashController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/


// Route::get('/',function(){
//     return view('welcome');
// });
// Checkout IFrame User
Route::get('/', [BkashController::class, 'pay'])->name('pay');
Route::post('/bkash/create', [BkashController::class, 'create'])->name('create');
Route::post('/bkash/execute', [BkashController::class, 'execute'])->name('execute');

Route::get('/success', [BkashController::class, 'success'])->name('success');
Route::get('/fail', [BkashController::class, 'fail'])->name('fail');

// Checkout IFrame Admin
Route::get('/bkash/query', [BkashController::class, 'query'])->name('query');
Route::get('/bkash/query-payment', [BkashController::class, 'queryPayment'])->name('query-payment');

Route::get('/bkash/search', [BkashController::class, 'search'])->name('search');
Route::get('/bkash/search-transaction', [BkashController::class, 'searchTransaction'])->name('search-transaction');

Route::get('/bkash/refund', [BkashController::class, 'getRefund'])->name('get-refund');
Route::post('/bkash/refund', [BkashController::class, 'refund'])->name('refund');

Route::get('/bkash/refund-status', [BkashController::class, 'getRefundStatus'])->name('get-refund-status');
Route::post('/bkash/refund-status', [BkashController::class, 'refundStatus'])->name('refund-status');


