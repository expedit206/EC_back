<?php

namespace App\Models;

use App\Models\User;
use App\Models\Produit;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = ['content'];
    // protected $fillable = ['sender_id', 'receiver_id', 'product_id', 'content'];
    // table
    protected $table = 'messages';
    
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }
    public function product()
    {
        return $this->belongsTo(Produit::class, 'product_id');
    }
}