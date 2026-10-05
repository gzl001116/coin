<?php
use App\Http\Controllers\WalletController;
use App\Http\Controllers\RechargeController;
use App\Http\Controllers\SellerController;
use Illuminate\Support\Facades\Route;
Route::prefix('v1')->group(function () {
    Route::post('/recharge/quote', [RechargeController::class, 'quote']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/wallet/balance', [WalletController::class, 'balance']);
        Route::post('/wallet/debit', [WalletController::class, 'debit']);
        Route::get('/seller/finance', [SellerController::class, 'finance']);
        Route::post('/seller/withdrawals', [SellerController::class, 'withdraw']);
    });
});
