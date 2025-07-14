<?php
// app/Models/User.php
namespace App\Models;

use App\Models\Litige;
use App\Models\Boutique;
use App\Models\Commande;
use App\Models\Abonnement;
use App\Models\Commercant;
use App\Models\Parrainage;
use App\Models\Collaboration;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable 
{
    // protected $primaryKey = 'id';
    // protected $keyType = 'string';
    // public $incrementing = false;

    protected $fillable = [
        'id',
        'nom',
        'telephone',
        'email',
        'ville',
        'mot_de_passe',
        'role',
        'premium',
        'parrain_id',
    ];

    protected $hidden = [
        'mot_de_passe',
    ];

    public function getAuthPassword()
    {
        return $this->mot_de_passe;
    }
    public function commercant()
    {
        return $this->hasOne(Commercant::class);
    }

    public function boutiques()
    {
        return $this->hasManyThrough(Boutique::class, Commercant::class, 'user_id', 'commercant_id');
    }

    public function collaborations()
    {
        return $this->hasMany(Collaboration::class);
    }

    public function commandesAcheteur()
    {
        return $this->hasMany(Commande::class, 'acheteur_id');
    }

    public function filleuls()
    {
        return $this->hasMany(Parrainage::class, 'parrain_id');
    }

    public function parrain()
    {
        return $this->belongsTo(User::class, 'parrain_id');
    }

    public function abonnements()
    {
        return $this->hasMany(Abonnement::class);
    }

    public function litiges()
    {
        return $this->hasMany(Litige::class);
    }
}