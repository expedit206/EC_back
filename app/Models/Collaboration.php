<?php

// app/Models/Collaboration.php
namespace App\Models;

use App\Models\User;
use App\Models\Produit;
use App\Models\Commercant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Collaboration extends Model
{
    use HasFactory;
    // protected $keyType = 'string';
    // public $incrementing = false;

    protected $fillable = [
        'id',
        'produit_id',
        'commercant_id',
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

    public function commercant()
    {
        return $this->belongsTo(Commercant::class);
    }
}