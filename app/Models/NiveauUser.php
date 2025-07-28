<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NiveauUser extends Model
{
 

    protected $table = 'niveaux_users';
    protected $fillable = ['user_id', 'niveau_id', 'points', 'statut', 'date_atteinte', 'jetons_attribues'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function parrainageNiveau()
    {
        return $this->belongsTo(ParrainageNiveau::class, 'niveau_id', 'id');
    }
}