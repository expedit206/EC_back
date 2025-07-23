<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Boost extends Model
{
    /**
     * Les attributs qui sont assignables en masse.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'produit_id',
        'boutique_id',
        'type',
        'start_date',
        'end_date',
        'statut',
        'cout_jetons',
    ];

    /**
     * Indique si les timestamps sont activés.
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * Relation avec l'utilisateur.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation avec le produit (si applicable).
     */
    public function produit()
    {
        return $this->belongsTo(Produit::class, 'produit_id');
    }

    /**
     * Relation avec la boutique (si applicable, à créer si nécessaire).
     */
    public function boutique()
    {
        return $this->belongsTo(Boutique::class, 'boutique_id');
    }
}