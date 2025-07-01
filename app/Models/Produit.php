<?php

// app/Models/Produit.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Produit extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'boutique_id',
        'nom',
        'description',
        'prix',
        'quantite',
        'photo_url',
        'collaboratif',
        'marge_min'
    ];

    public function boutique()
    {
        return $this->belongsTo(Boutique::class);
    }

    public function collaborations()
    {
        return $this->hasMany(Collaboration::class);
    }

    public function commandes()
    {
        return $this->hasMany(Commande::class);
    }
}