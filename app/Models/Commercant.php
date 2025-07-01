<?php
// app/Models/Commercant.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Commercant extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = ['id', 'user_id', 'nom', 'description', 'logo', 'ville', 'actif'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function boutiques()
    {
        return $this->hasMany(Boutique::class);
    }
}