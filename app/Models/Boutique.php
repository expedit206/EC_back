<?php

// app/Models/Boutique.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Boutique extends Model
{
    use HasFactory;
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = ['id', 'commercant_id', 'nom', 'description', 'logo', 'ville', 'actif'];

    public function commercant()
    {
        return $this->belongsTo(Commercant::class);
    }

    public function produits()
    {
        return $this->hasMany(Produit::class);
    }
}