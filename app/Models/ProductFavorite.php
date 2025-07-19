<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductFavorite extends Model
{

    protected $fillable = ['produit_id', 'user_id'];
}