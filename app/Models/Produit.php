<?php

// app/Models/Produit.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Produit extends Model
{
    use HasFactory;
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'commercant_id',
        'category_id',
        'nom',
        'ville',
        'description',
        'prix',
        'quantite',
        'photo_url',
        'collaboratif',
        'marge_min'
    ];

    public function commercant()
    {
        return $this->belongsTo(Commercant::class);
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