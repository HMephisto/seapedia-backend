<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;
    
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'reviewer_name',
        'rating',
        'comment',
    ];

    const CREATED_AT = 'created_at';

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
