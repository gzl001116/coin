<?php
namespace App\Http\Controllers;

use App\Models\SellerAccount;
use App\Support\SiteContext;
use Illuminate\Http\Request;

class SellerFinanceController extends Controller
{
    public function __construct(private SiteContext $sites) {}

    public function show(Request $request)
    {
        $site = $this->sites->requireScope($request->user(), 'seller.finance.read');
        $account = SellerAccount::where('user_id', $request->user()->id)
            ->where('site_id', $site->id)->firstOrFail();

        return response()->json([
            'total_income' => $account->total_income,
            'pending_income' => $account->pending_income,
            'available_income' => $account->available_income,
            'withdrawal_fee_rate' => (float) config('coin.seller_withdrawal_fee_rate', 0.05),
        ]);
    }
}
