<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    use HasFactory;
    public $timestamps = false;
     protected $fillable = [
        'buyer_id',
        'store_id'
    ];

    public function items()
    {
        return $this->hasMany(CartItem::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}
