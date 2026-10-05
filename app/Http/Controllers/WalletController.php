<?php
namespace App\Http\Controllers;

use App\Services\WalletService;
use App\Support\SiteContext;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function __construct(private WalletService $wallet, private SiteContext $sites) {}

    public function balance(Request $request)
    {
        $this->sites->requireScope($request->user(), 'wallet.balance.read');
        return response()->json([
            'points_balance' => $this->wallet->balanceFor($request->user()),
        ]);
    }

    public function debit(Request $request)
    {
        $site = $this->sites->requireScope($request->user(), 'wallet.debit.create');
        $data = $request->validate([
            'points' => ['required','numeric','gt:0'],
            'order_id' => ['required','string','max:100'],
            'idempotency_key' => ['required','string','max:100'],
        ]);

        $entry = $this->wallet->debit(
            $request->user(),
            $site->id,
            $data['order_id'],
            $data['idempotency_key'],
            number_format((float)$data['points'], 2, '.', '')
        );

        return response()->json([
            'success' => true,
            'points' => $data['points'],
            'order_id' => $data['order_id'],
            'ledger_id' => $entry->id,
        ]);
    }
}
