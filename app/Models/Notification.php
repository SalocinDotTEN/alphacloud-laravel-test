<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'notifiable_id',
        'data',
        'latest_bid_price',
        'user_last_bid_price',
        'read_at'
    ];

    protected $casts = [
        'data' => 'array',
        'latest_bid_price' => 'decimal:2',
        'user_last_bid_price' => 'decimal:2',
    ];

    public function notifiable()
    {
        return $this->belongsTo(User::class, 'notifiable_id');
    }
}
