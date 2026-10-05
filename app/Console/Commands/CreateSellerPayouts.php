<?php
namespace App\Console\Commands;

use App\Models\SellerAccount;
use App\Models\Withdrawal;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateSellerPayouts extends Command
{
    protected $signature = 'sellers:payouts';
    protected $description = 'Create weekly seller payout orders from available balances';

    public function handle(): int
    {
        $rate = (string) config('coin.seller_withdrawal_fee_rate', 0.05);
        SellerAccount::query()->where('available_income', '>', 0)->chunkById(100, function ($accounts) use ($rate) {
            foreach ($accounts as $account) {
                $lock = Cache::lock('seller:payout:' . $account->id, 15);
                $lock->block(5);
                try {
                    DB::transaction(function () use ($account, $rate) {
                        $fresh = SellerAccount::whereKey($account->id)->lockForUpdate()->first();
                        if (!$fresh || bccomp((string)$fresh->available_income, '0.00', 2) <= 0) return;

                        $week = now()->startOfWeek()->format('Ymd');
                        $key = 'weekly:' . $fresh->site_id . ':' . $fresh->user_id . ':' . $week;
                        if (Withdrawal::where('idempotency_key', $key)->exists()) return;

                        $amount = (string)$fresh->available_income;
                        $fee = bcmul($amount, $rate, 2);
                        $payout = bcsub($amount, $fee, 2);

                        Withdrawal::create([
                            'seller_id' => $fresh->user_id,
                            'site_id' => $fresh->site_id,
                            'amount' => $amount,
                            'fee_rate' => $rate,
                            'fee_amount' => $fee,
                            'payout_amount' => $payout,
                            'status' => 'scheduled',
                            'idempotency_key' => $key,
                            'scheduled_at' => now(),
                        ]);

                        $fresh->available_income = '0.00';
                        $fresh->save();
                    });
                } finally {
                    $lock->release();
                }
            }
        });

        $this->info('Weekly seller payout orders created.');
        return self::SUCCESS;
    }
}
