<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VenueRequest extends Model
{
    use HasFactory;

    // الحقول المسموح بتعبئتها دفعة واحدة (Mass Assignment)
    protected $fillable = [
        'owner_id',
        'venue_id',
        'name',
        'address',
        'capacity',
        'price',
        'description',
        'cover_image',
        'images',
        'status',
        'admin_notes'
    ];

    protected $casts = [
        'images' => 'array',
    ];
    /**
     * علاقة الطلب مع صاحب الصالة (المستخدم)
     */
    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * علاقة الطلب مع الصالة الحالية (إن وجدت)
     */
    public function venue()
    {
        return $this->belongsTo(Venue::class, 'venue_id');
    }
}
