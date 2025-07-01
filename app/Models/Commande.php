<?php

// app/Models/Commande.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Commande extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'acheteur_id',
        'produit_id',
        'commercant_id',
        'collaborateur_id',
        'statut',
        'montant_total',
        'paiement_statut'
    ];

    protected $casts = [
        'statut' => 'string', // ['en_attente', 'livrée', 'litige']
        'paiement_statut' => 'string', // ['en_attente', 'payé', 'remboursé']
    ];

    public function acheteur()
    {
        return $this->belongsTo(User::class, 'acheteur_id');
    }

    public function commercant()
    {
        return $this->belongsTo(Commercant::class);
    }

    public function collaborateur()
    {
        return $this->belongsTo(User::class, 'collaborateur_id');
    }

    public function produit()
    {
        return $this->belongsTo(Produit::class);
    }

    public function litige()
    {
        return $this->hasOne(Litige::class);
    }
}