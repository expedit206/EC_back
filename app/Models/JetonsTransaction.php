<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JetonsTransaction extends Model
{
    /**
     * Les attributs qui sont assignables en masse.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'type',
        'montant',
        'description',
    ];

    /**
     * Indique si les timestamps sont activés.
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * Relation avec l'utilisateur.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}