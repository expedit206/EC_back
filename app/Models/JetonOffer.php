<?php

namespace App\Models;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Eloquent\Model;

class JetonOffer extends Model
{
    
    protected $fillable = ['user_id', 'nombre_jetons', 'prix_unitaire', 'total_prix', 'wallet_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }
 
}