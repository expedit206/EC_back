<?php

// app/Models/Litige.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Litige extends Model
{
    use HasFactory;
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'commande_id',
        'user_id',
        'description',
        'statut',
        'preuves'
    ];

    protected $casts = [
        'statut' => 'string', // ['ouvert', 'résolu', 'rejeté']
        'preuves' => 'array', // URLs des photos/vidéos
    ];

    public function commande()
    {
        return $this->belongsTo(Commande::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
