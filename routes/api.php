<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TransactionController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user()->load('wallet');
    });

    Route::post('/logout', [AuthController::class, 'logout']);

    Route::post('/deposit', [TransactionController::class, 'deposit']);
    Route::post('/transfer', [TransactionController::class, 'transfer']);
    Route::post('/transactions/{transaction}/reverse', [TransactionController::class, 'reverse']);
    Route::get('/transactions', [TransactionController::class, 'index']);
    Route::get('/transactions/{transaction}', [TransactionController::class, 'show']);
    Route::delete('/transactions/{transaction}', [TransactionController::class, 'destroy']);
    Route::get('/wallet', function (Request $request) {
        return $request->user()->wallet;
    });
    Route::get('/wallet/transactions', [TransactionController::class, 'walletTransactions']);
});
