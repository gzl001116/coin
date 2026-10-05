<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class WalletLedger extends Model {
 protected $fillable=['user_id','site_id','type','gross_points','fee_rate','fee_points','net_points','order_id','idempotency_key','metadata'];
 protected $casts=['metadata'=>'array','gross_points'=>'decimal:2','fee_rate'=>'decimal:4','fee_points'=>'decimal:2','net_points'=>'decimal:2'];
}