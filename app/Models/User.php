<?php
// app/Models/User.php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'nom',
        'email',
        'telephone',
        'ville',
        'mot_de_passe',
        'photo',
        'premium',
        'parrain_id',
        'code_parrainage',
        'solde'
    ];

    protected $hidden = ['mot_de_passe'];

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