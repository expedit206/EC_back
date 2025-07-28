<?php
// app/Models/Commercant.php
namespace App\Models;

use App\Models\User;
use App\Models\Produit;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Commercant extends Model
{
    use HasFactory;
    protected $keyType = 'string';
    public $incrementing = false;

    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) \Str::uuid();
            }
        });
    }

    protected $fillable = ['id', 'user_id', 'nom', 'description', 'logo', 'ville'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

   

    public function produits()
    {
        return $this->hasMany(Produit::class);
    }

    public function ratings()
    {
        return $this->hasMany(CommercantRating::class);
    }

    public function getAverageRatingAttribute()
    {
        return $this->ratings()->avg('rapiating') ?? 0;
    }
}