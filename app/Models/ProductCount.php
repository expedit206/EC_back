<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductCount extends Model
{

    protected $table = 'product_counts';
    protected $primaryKey = 'produit_id';
    public $incrementing = false; // Puisque produit_id est un UUID
    protected $keyType = 'string';  
    protected $fillable = ['views_count', 'favorites_count'];

}