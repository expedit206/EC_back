<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParrainageNiveau extends Model
{
    
    protected $table = 'niveaux_parrainages';
    protected $fillable = ['id', 'niveau', 'points_requis', 'jetons_attribues', 'description'];

    public function niveaux_users()
    {
        return $this->hasMany(NiveauUser::class, 'niveau_id', 'id');
    }

  
}