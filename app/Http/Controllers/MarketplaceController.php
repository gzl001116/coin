<?php
namespace App\Http\Controllers;

use App\Models\MarketplaceProduct;
use App\Services\MarketplaceService;
use App\Support\SiteContext;
use Illuminate\Http\Request;

class MarketplaceController extends Controller
{
    public function __construct(private MarketplaceService $market, private SiteContext $sites) {}

    public function purchase(Request $request)
    {
        $site = $this->sites->requireScope($request->user(), 'wallet.debit.create');
        $data = $request->validate([
            'product_id' => ['required','string','max:100'],
            'order_id' => ['required','string','max:100'],
            'idempotency_key' => ['required','string','max:100'],
        ]);

        $product = MarketplaceProduct::where('site_id', $site->id)
            ->where('product_id', $data['product_id'])
            ->where('status', 'active')
            ->firstOrFail();

        $order = $this->market->purchase(
            $request->user(),
            $site->id,
            $product->seller_id,
            $product->product_id,
            (string)$product->points,
            $data['order_id'],
            $data['idempotency_key']
        );

        return response()->json(['success'=>true,'order'=>$order]);
    }
}
