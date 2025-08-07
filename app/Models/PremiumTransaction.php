<?php
// app/Models/PremiumTransaction.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PremiumTransaction extends Model
{
    protected $table = 'premium_transactions';
    protected $fillable = [
        'id',
        'user_id',
        'type_abonnement',
        'montant',
        'methode_paiement',
        'transaction_id_mesomb',
        'statut',
        'date_transaction',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}