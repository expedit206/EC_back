<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class JetonOffer extends Model
{
    
    protected $fillable = ['user_id', 'nombre_jetons', 'prix_unitaire', 'total_prix'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}