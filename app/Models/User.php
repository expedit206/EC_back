<?php
// app/Models/User.php
namespace App\Models;

use App\Models\Abonnement;
use App\Models\Commercant;
use App\Models\NiveauUser;
use App\Models\Parrainage;
use App\Models\Collaboration;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable 
{
    // protected $primaryKey = 'id';
    // protected $keyType = 'string';
    // public $incrementing = false;
    use HasFactory;

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
        'token',
        'parrainage_code',
        'jetons',
        'photo',
        'is_premium',
        'subscription_ends_at'
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

    public function collaborations()
    {
        return $this->hasMany(Collaboration::class);
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


    public function jetonsTransactions()
    {
        return $this->hasMany(JetonsTransaction::class);
    }

    public function boosts()
    {
        return $this->hasMany(Boost::class);
    }

    //niveau user
    public function niveaux_users()
    {
        return $this->hasMany(NiveauUser::class);
    }

}