<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'venue_id',
        'date',
        'start_time',
        'end_time',
        'guests_count',
        'total_price',
        'note',
        'status',
        'rejection_reason'
    ];

    // علاقة المناسبة بالزبون الذي أنشأها
    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    // علاقة المناسبة بالصالة المحجوزة
    public function venue()
    {
        return $this->belongsTo(Venue::class, 'venue_id');
    }
}
