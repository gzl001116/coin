<?php

use App\Http\Controllers\RechargeController;
use App\Http\Controllers\SellerFinanceController;
use App\Http\Controllers\SellerController;
use App\Http\Controllers\MarketplaceController;
use App\Http\Controllers\WalletController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/recharge/quote', [RechargeController::class, 'quote']);

    Route::middleware('auth:api')->group(function () {
        Route::get('/wallet/balance', [WalletController::class, 'balance'])
            ->middleware('scope:wallet.balance.read');

        Route::post('/wallet/debit', [WalletController::class, 'debit'])
            ->middleware('scope:wallet.debit.create');

        Route::post('/marketplace/purchase', [MarketplaceController::class, 'purchase'])
            ->middleware('scope:wallet.debit.create');

        Route::get('/seller/finance', [SellerFinanceController::class, 'show'])
            ->middleware('scope:seller.finance.read');

        Route::post('/seller/withdrawals', [SellerController::class, 'withdraw'])
            ->middleware('scope:seller.withdraw.create');

    });
});
