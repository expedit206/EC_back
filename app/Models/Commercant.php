<?php
// app/Models/Commercant.php
namespace App\Models;

use App\Models\User;
use App\Models\Boutique;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class Commercant extends Model
{
    use HasFactory;
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