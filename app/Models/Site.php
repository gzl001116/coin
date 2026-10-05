<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Site extends Model {
 protected $fillable=['name','domain','owner_user_id','client_id','client_secret_hash','status','scopes','verified_at'];
 protected $hidden=['client_secret_hash'];
 protected $casts=['scopes'=>'array','verified_at'=>'datetime'];
}