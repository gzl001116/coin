<?php
namespace App\Services;

use App\Models\Wallet;
use App\Models\WalletLedger;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class WalletService
{
    public function balanceFor($user): string
    {
        return (string) (Wallet::where('user_id', $user->id)->value('points_balance') ?? '0.00');
    }

    public function debit($user, int $siteId, string $orderId, string $idempotencyKey, string $points): WalletLedger
    {
        $lock = Cache::lock('wallet:' . $user->id, 10);
        $lock->block(5);
        try {
            return DB::transaction(function () use ($user, $siteId, $orderId, $idempotencyKey, $points) {
                $existing = WalletLedger::where('idempotency_key', $idempotencyKey)->lockForUpdate()->first();
                if ($existing) {
                    return $existing;
                }

                $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->firstOrFail();
                if (bccomp((string) $wallet->points_balance, $points, 2) < 0) {
                    throw new \RuntimeException('INSUFFICIENT_POINTS');
                }

                $wallet->points_balance = bcsub((string) $wallet->points_balance, $points, 2);
                $wallet->save();

                return WalletLedger::create([
                    'user_id' => $user->id,
                    'site_id' => $siteId,
                    'type' => 'consume',
                    'gross_points' => $points,
                    'fee_rate' => '0',
                    'fee_points' => '0',
                    'net_points' => '-' . $points,
                    'order_id' => $orderId,
                    'idempotency_key' => $idempotencyKey,
                ]);
            });
        } finally {
            optional($lock)->release();
        }
    }
}
