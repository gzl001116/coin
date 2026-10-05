<?php
namespace App\Services;

use App\Models\MarketplaceOrder;
use App\Models\Site;
use App\Models\SellerAccount;
use App\Models\SellerLedger;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class MarketplaceService
{
    public function __construct(private WalletService $wallet) {}

    public function purchase($user, int $siteId, int $sellerId, string $productId, string $points, string $orderId, string $idempotencyKey): MarketplaceOrder
    {
        $lock = Cache::lock('order:' . $siteId . ':' . $orderId, 15);
        $lock->block(5);
        try {
            return DB::transaction(function () use ($user, $siteId, $sellerId, $productId, $points, $orderId, $idempotencyKey) {
                $old = MarketplaceOrder::where('idempotency_key', $idempotencyKey)->lockForUpdate()->first();
                if ($old) return $old;

                $this->wallet->debit($user, $siteId, $orderId, 'wallet:' . $idempotencyKey, $points);

                $order = MarketplaceOrder::create([
                    'order_id' => $orderId,
                    'user_id' => $user->id,
                    'site_id' => $siteId,
                    'seller_id' => $sellerId,
                    'product_id' => $productId,
                    'points' => $points,
                    'status' => 'paid',
                    'idempotency_key' => $idempotencyKey,
                    'paid_at' => now(),
                ]);

                $rate = (string) Site::whereKey($siteId)->value('commission_rate');
                $fee = bcmul($points, $rate, 2);
                $net = bcsub($points, $fee, 2);

                SellerLedger::create([
                    'seller_id' => $sellerId,
                    'site_id' => $siteId,
                    'order_id' => $orderId,
                    'gross_income' => $points,
                    'platform_fee_rate' => $rate,
                    'platform_fee' => $fee,
                    'net_income' => $net,
                    'status' => 'pending',
                ]);

                $account = SellerAccount::firstOrCreate(
                    ['user_id' => $sellerId, 'site_id' => $siteId],
                    ['pending_income' => '0.00', 'available_income' => '0.00', 'total_income' => '0.00']
                );
                SellerAccount::whereKey($account->id)->increment('pending_income', $net);
                SellerAccount::whereKey($account->id)->increment('total_income', $net);

                return $order;
            });
        } finally {
            $lock->release();
        }
    }
}
