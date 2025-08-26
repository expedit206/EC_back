<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Parrainage extends Model
{
    protected $fillable = ['parrain_id', 'filleul_id', 'code', 'statut', 'gains', 'date_activation'];
    protected $casts = [
        'gains' => 'decimal:2',
    ];

    public function parrain()
    {
        return $this->belongsTo(User::class, 'parrain_id');
    }

    public function filleul()
    {
        return $this->belongsTo(User::class, 'filleul_id');
    }

    public function produits()
    {
        return $this->hasManyThrough(Produit::class, User::class, 'id', 'commercant_id', 'filleul_id', 'id');
    }
}