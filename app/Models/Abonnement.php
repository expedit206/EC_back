<?php
// app/Models/Abonnement.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Abonnement extends Model
{
    use HasFactory;
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'user_id',
        'plan',
        'debut',
        'fin',
        'actif'
    ];

    protected $casts = [
        'debut' => 'datetime',
        'fin' => 'datetime',
        'actif' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
