<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'event_name',
        'event_type_id',
        'venue_id',
        'date',
        'start_time',
        'end_time',
        'guests_count',
        'total_price',
        'invoice_id',
        'payment_id',
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

    // علاقة المناسبة بالفاتورة
    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    // علاقة المناسبة بالدفع
    public function payment()
    {
        return $this->belongsTo(Payment::class, 'payment_id');
    }

    // علاقة المناسبة بالخدمات (جدول وسيط)
    public function services()
    {
        return $this->belongsToMany(Service::class, 'event_services')
            ->withPivot('quantity', 'price', 'status', 'rejection_reason')
            ->withTimestamps();
    }

    // علاقة ا��مناسبة بـ event_services (للوصول للتفاصيل الكاملة)
    public function eventServices()
    {
        return $this->hasMany(EventService::class, 'event_id');
    }


}
