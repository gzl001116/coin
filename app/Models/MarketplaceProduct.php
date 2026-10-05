<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketplaceProduct extends Model
{
    protected $fillable = ['site_id','seller_id','product_id','title','points','status','metadata'];
    protected $casts = ['points'=>'decimal:2','metadata'=>'array'];
}
