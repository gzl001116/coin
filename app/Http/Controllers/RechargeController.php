<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
class RechargeController extends Controller {
 public function quote(Request $request) {
  $p=(float)$request->validate(['points'=>['required','numeric','gt:0']])['points'];
  $r=(float)config('coin.recharge_fee_rate',.03); $payment=round($p/(1-$r),2);
  return response()->json(['points'=>$p,'payment_amount'=>$payment,'fee_rate'=>$r,'fee_amount'=>round($payment-$p,2)]);
 }
}
