<?php

namespace App\Models;

use App\Models\Produit;
use App\Models\Commande;
use Illuminate\Database\Eloquent\Model;

class CommandeProduit extends Model
{
    protected $fillable = ['commande_id', 'produit_id', 'quantite', 'prix'];
    protected $keyType = 'string';
    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->getKey()) {
                $model->{$model->getKeyName()} = (string) \Str::uuid();
            }
        });
    }
    
    public function commande()
    {
        return $this->belongsTo(Commande::class);
    }

    public function produit()
    {
        return $this->belongsTo(Produit::class, 'produit_id');
    }

}