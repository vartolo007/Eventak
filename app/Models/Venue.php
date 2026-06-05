<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Venue extends Model
{
    use HasFactory;

    protected $fillable = [
        'owner_id',
        'name',
        'address',
        'capacity',
        'price',
        'description',
        'cover_image',
        'images'
    ];

    // تحويل حقل الـ JSON الخاص بالصور الإضافية إلى مصفوفة PHP تلقائياً
    protected $casts = [
        'images' => 'array',
    ];

    // علاقة الصالة بصاحبها (كل صالة تنتمي لمستخدم من نوع صاحب صالة)
    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
}
