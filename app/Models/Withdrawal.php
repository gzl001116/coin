<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Withdrawal extends Model
{
    protected $fillable = ['seller_id','site_id','amount','fee_rate','fee_amount','payout_amount','status','idempotency_key','scheduled_at','provider_tx_id'];
    protected $casts = ['amount'=>'decimal:2','fee_rate'=>'decimal:4','fee_amount'=>'decimal:2','payout_amount'=>'decimal:2','scheduled_at'=>'datetime'];
}
