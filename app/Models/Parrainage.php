<?php

// app/Models/Parrainage.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Parrainage extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'parrain_id',
        'filleul_id',
        'niveau',
        'recompense'
    ];

    public function parrain()
    {
        return $this->belongsTo(User::class, 'parrain_id');
    }

    public function filleul()
    {
        return $this->belongsTo(User::class, 'filleul_id');
    }
}