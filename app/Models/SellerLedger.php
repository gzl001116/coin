<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class SellerLedger extends Model {
 protected $fillable=['seller_id','site_id','order_id','gross_income','platform_fee_rate','platform_fee','net_income','status','metadata'];
 protected $casts=['gross_income'=>'decimal:2','platform_fee_rate'=>'decimal:4','platform_fee'=>'decimal:2','net_income'=>'decimal:2','metadata'=>'array'];
}