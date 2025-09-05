<?php

// app/Models/Produit.php
namespace App\Models;

use App\Models\User;
use App\Models\Boost;
use App\Models\Category;
use App\Models\Commercant;
use App\Models\ProductView;
use App\Models\ProductCount;
use Illuminate\Http\Request;
use App\Models\Collaboration;
use App\Models\ProductFavorite;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Produit extends Model
{
    use HasFactory;
    protected $keyType = 'string';
    public $incrementing = false;

    protected $casts = [
        'photos' => 'array', // Cast la colonne JSON en tableau PHP
    ];
    protected $fillable = [
        'id',
        'commercant_id',
        'category_id',
        'nom',
        'ville',
        'description',
        'prix',
        'quantite',
        'photos',
        'collaboratif',
        'marge_min',
        'original_commercant_id'
    ];
    // protected $appends = ['favorites_count', 'views_count'];
    
    


    
    public function commercant()
    {
        return $this->belongsTo(Commercant::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function collaborations()
    {
        return $this->hasMany(Collaboration::class);
    }

  
    public function category()
    {
        return $this->belongsTo(Category::class);
    }


    public function views()
    {
        return $this->hasMany(ProductView::class);
    }

   


    public function getFavoritesCountAttribute()
    {
        return $this->favorites()->count();
    }


    public function favorites()
    {
        return $this->hasMany(ProductFavorite::class);
    }


    public function boosts()
    {
        return $this->hasMany(Boost::class, 'produit_id');
    }

    public function counts()
    {
        return $this->hasOne(ProductCount::class, 'produit_id', 'id');
    }


    public function originalCommercant()
    {
        return $this->belongsTo(Commercant::class, 'original_commercant_id');
    }
    
    public function isFavoritedByUser($user = null)
    {
        if ($user) {
            return ProductFavorite::where('produit_id', $this->id)
                ->where('user_id', $user->id)
                ->exists();
        }
        return false; // Retourne false si aucun utilisateur
    }

    // Méthode pour obtenir la date de fin du boost
    public function getBoostedUntilAttribute()
    {
        $boost = $this->boosts()
            ->where('statut', 'actif')
            ->where('end_date', '>', now())
            ->latest('end_date')
            ->first();
        return $boost ? $boost->end_date : null;
    }
}