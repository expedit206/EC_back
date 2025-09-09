<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JetonTrade extends Model
{
    use HasFactory;

    protected $table = 'jeton_trades';

    /**
     * Colonnes modifiables en masse
     */
    protected $fillable = [
        'vendeur_id',
        'acheteur_id',
        'offer_id',
        'nombre_jetons',
        'montant_total',
        'commission_plateforme',
        'montant_net_vendeur',
        'methode_paiement',
        'transaction_id_mesomb_vendeur',
        'transaction_id_mesomb_plateforme',
        'statut',
        'date_transaction',
    ];

    /**
     * Casts pour les types de données
     */
    protected $casts = [
        'nombre_jetons' => 'integer',
        'montant_total' => 'float',
        'commission_plateforme' => 'float',
        'montant_net_vendeur' => 'float',
        'date_transaction' => 'datetime',
    ];

    /**
     * Relations
     */

    // Relation avec le vendeur (User)
    public function vendeur()
    {
        return $this->belongsTo(User::class, 'vendeur_id');
    }

    // Relation avec l’acheteur (User)
    public function acheteur()
    {
        return $this->belongsTo(User::class, 'acheteur_id');
    }

    // Relation avec l’offre
    public function offer()
    {
        return $this->belongsTo(JetonOffer::class, 'offer_id');
    }
}