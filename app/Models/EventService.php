<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventService extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'service_id',
        'quantity',
        'price',
        'status',
        'rejection_reason'
    ];

    // علاقة event_services بالمناسبة
    public function event()
    {
        return $this->belongsTo(Event::class, 'event_id');
    }

    // علاقة event_services بالخدمة
    public function service()
    {
        return $this->belongsTo(Service::class, 'service_id');
    }
}
