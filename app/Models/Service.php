<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_id',
        'category_id',
        'name',
        'description',
        'price',
        'images',
        'status'
    ];

    protected $casts = [
        'images' => 'array',
    ];

    // علاقة الخدمة بالمورد
    public function vendor()
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    // علاقة الخدمة بالتصنيف
    public function category()
    {
        return $this->belongsTo(ServiceCategory::class, 'category_id');
    }

    // علاقة الخدمة بالمناسبات
    public function events()
    {
        return $this->belongsToMany(Event::class, 'event_services')
            ->withPivot('quantity', 'price', 'status', 'rejection_reason')
            ->withTimestamps();
    }
}
