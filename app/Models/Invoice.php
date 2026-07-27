<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'venue_price',
        'services_total',
        'total_amount',
        'status'
    ];

    // علاقة الفاتورة بالمناسبة
    public function event()
    {
        return $this->belongsTo(Event::class, 'event_id');
    }

    // علاقة الفاتورة بالدفع
    public function payment()
    {
        return $this->hasOne(Payment::class, 'invoice_id');
    }


}
