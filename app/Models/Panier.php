<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Panier extends Model
{
    // 'user_id' => $user->id,
    //         'produit_id' => $data['produit_id'],
    //         'quantite' => 1,
    //
    protected $fillable = [
        'user_id',
        'produit_id',
        'quantite',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->getKey()) {
                $model->{$model->getKeyName()} = (string) \Str::uuid();
            }
        });
    }

    public function produit()
    {
        return $this->belongsTo(Produit::class, 'produit_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    
}