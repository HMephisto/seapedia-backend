<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderStatusHistory extends Model
{
    use HasFactory;
    public $timestamps = false; 
    const CREATED_AT = 'created_at';
    const UPDATED_AT = null;

    protected $fillable = [
        'order_id',
        'status',
        'notes',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
