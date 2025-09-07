<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class JetonTransaction extends Model
{
    /**
     * Les attributs qui sont assignables en masse.
     *
     * @var array
     */
    protected $table = 'jetons_transactions';
    protected $fillable = [
        'id',
        'user_id',
        'nombre_jetons',
        'montant',
        'methode_paiement',
        'phone_number',
        'transaction_id_mesomb',
        'statut',
        'date_transaction',
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
}