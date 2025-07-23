<?php

// app/Models/Produit.php
namespace App\Models;

use App\Models\User;
use App\Models\Category;
use App\Models\Commercant;
use App\Models\ProductView;
use App\Models\Collaboration;
use App\Models\ProductFavorite;
use Illuminate\Support\Facades\Redis;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Produit extends Model
{
    use HasFactory;
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'commercant_id',
        'category_id',
        'nom',
        'ville',
        'description',
        'prix',
        'quantite',
        'photo_url',
        'collaboratif',
        'marge_min'
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

    public function getViewsCountAttribute()
    {
        $key = "produit:views:{$this->id}";
        return Redis::get($key) ?? $this->views()->count();
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

}