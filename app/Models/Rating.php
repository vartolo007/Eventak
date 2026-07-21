<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rating extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'customer_id',
        'rating',
        'comment',
    ];

    // علاقة التقييم بالمناسبة
    public function event()
    {
        return $this->belongsTo(Event::class, 'event_id');
    }

    // علاقة التقييم بالزبون الذي كتبه
    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }
}
