<?php
namespace App\Http\Controllers;

use App\Models\SellerAccount;
use App\Models\Withdrawal;
use App\Support\SiteContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SellerController extends Controller
{
    public function __construct(private SiteContext $sites) {}

    public function withdraw(Request $request)
    {
        $site = $this->sites->requireScope($request->user(), 'seller.withdraw.create');
        $data = $request->validate([
            'amount' => ['required','numeric','gt:0'],
            'idempotency_key' => ['required','string','max:100'],
        ]);

        $result = DB::transaction(function () use ($request, $site, $data) {
            $old = Withdrawal::where('idempotency_key', $data['idempotency_key'])->lockForUpdate()->first();
            if ($old) return $old;

            $account = SellerAccount::where('user_id', $request->user()->id)
                ->where('site_id', $site->id)->lockForUpdate()->firstOrFail();

            $amount = number_format((float)$data['amount'], 2, '.', '');
            if (bccomp((string)$account->available_income, $amount, 2) < 0) {
                abort(422, 'INSUFFICIENT_SELLER_FUNDS');
            }

            $rate = (string) config('coin.seller_withdrawal_fee_rate', 0.05);
            $fee = bcmul($amount, $rate, 2);
            $payout = bcsub($amount, $fee, 2);

            $account->available_income = bcsub((string)$account->available_income, $amount, 2);
            $account->save();

            return Withdrawal::create([
                'seller_id' => $request->user()->id,
                'site_id' => $site->id,
                'amount' => $amount,
                'fee_rate' => $rate,
                'fee_amount' => $fee,
                'payout_amount' => $payout,
                'status' => 'scheduled',
                'idempotency_key' => $data['idempotency_key'],
                'scheduled_at' => now()->next('Friday'),
            ]);
        });

        return response()->json(['success'=>true,'withdrawal'=>$result]);
    }
}
