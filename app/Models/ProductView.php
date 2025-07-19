<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductView extends Model
{
    //fillable

    protected $fillable = ['produit_id', 'user_id'];
}