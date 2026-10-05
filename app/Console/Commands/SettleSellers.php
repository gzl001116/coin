<?php
namespace App\Console\Commands;

use App\Models\SellerAccount;
use App\Models\SellerLedger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SettleSellers extends Command
{
    protected $signature = 'sellers:settle';
    protected $description = 'Move pending seller income to available balance';

    public function handle(): int
    {
        SellerAccount::query()->where('pending_income', '>', 0)->chunkById(100, function ($accounts) {
            foreach ($accounts as $account) {
                $lock = Cache::lock('seller:settle:' . $account->id, 10);
                $lock->block(5);
                try {
                    DB::transaction(function () use ($account) {
                        $fresh = SellerAccount::whereKey($account->id)->lockForUpdate()->first();
                        if (!$fresh || bccomp((string)$fresh->pending_income, '0.00', 2) <= 0) return;

                        $amount = (string)$fresh->pending_income;
                        $fresh->available_income = bcadd((string)$fresh->available_income, $amount, 2);
                        $fresh->pending_income = '0.00';
                        $fresh->save();

                        SellerLedger::where('seller_id', $fresh->user_id)
                            ->where('site_id', $fresh->site_id)
                            ->where('status', 'pending')
                            ->update(['status' => 'available']);
                    });
                } finally {
                    $lock->release();
                }
            }
        });

        $this->info('Seller settlement completed.');
        return self::SUCCESS;
    }
}
