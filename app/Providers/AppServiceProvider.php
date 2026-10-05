<?php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Passport::tokensCan([
            'wallet.balance.read' => 'Read current wallet balance',
            'wallet.debit.create' => 'Debit wallet for a purchase',
            'seller.finance.read' => 'Read seller finance for the current site',
            'seller.withdraw.create' => 'Create seller withdrawals for the current site',
        ]);
    }
}
