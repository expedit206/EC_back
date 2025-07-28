<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommercantRating extends Model
{
    protected $fillable = ['rating', 'commercant_id', 'user_id'];

    public function commercant()
    {
        return $this->belongsTo(Commercant::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}