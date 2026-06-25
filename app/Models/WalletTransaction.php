<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WalletTransaction extends Model
{
    public $timestamps = false;
 
    protected $fillable = [
        'wallet_id',
        'type',
        'amount',
        'description',
        'created_at',
    ];
 
    protected $casts = [
        'amount'     => 'decimal:2',
        'created_at' => 'datetime',
    ];
 
    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }
}