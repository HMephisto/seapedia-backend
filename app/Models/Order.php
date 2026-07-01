<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    public $timestamps = false;
    
    protected $fillable = [
        'buyer_id',
        'store_id',
        'address_id',
        'voucher_id',
        'promo_id',
        'shipping_recipient_name',
        'shipping_phone',
        'shipping_address',
        'delivery_method',
        'subtotal',
        'discount_amount',
        'delivery_fee',
        'ppn_amount',
        'final_total',
        'status',
        'expired_at',
        'returned_at',
    ];

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function history()
    {
        return $this->hasMany(OrderStatusHistory::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function address()
    {
        return $this->belongsTo(Address::class);
    }
}
