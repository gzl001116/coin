<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Wallet extends Model {
 protected $fillable=['user_id','points_balance'];
 protected $casts=['points_balance'=>'decimal:2'];
}
