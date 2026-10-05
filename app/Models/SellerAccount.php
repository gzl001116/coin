<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class SellerAccount extends Model {
 protected $fillable=['user_id','site_id','pending_income','available_income','total_income'];
 protected $casts=['pending_income'=>'decimal:2','available_income'=>'decimal:2','total_income'=>'decimal:2'];
}