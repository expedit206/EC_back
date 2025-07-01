<?php

// app/Models/Collaboration.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Collaboration extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'produit_id',
        'user_id',
        'prix_revente',
        'statut',
        'gains_totaux'
    ];

    protected $casts = [
        'statut' => 'string', // Pour gérer l'enum ['en_attente', 'validée', 'refusée']
    ];

    public function produit()
    {
        return $this->belongsTo(Produit::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}