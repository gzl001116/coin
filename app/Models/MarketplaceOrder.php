<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class MarketplaceOrder extends Model { protected $fillable=['order_id','user_id','site_id','seller_id','product_id','points','status','idempotency_key','metadata','paid_at']; protected $casts=['points'=>'decimal:2','metadata'=>'array','paid_at'=>'datetime']; }
