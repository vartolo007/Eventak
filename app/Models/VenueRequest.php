<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Model;

class VenueRequest extends Model
{
    use HasFactory;

    // الحقول المسموح بتعبئتها دفعة واحدة (Mass Assignment)
    protected $fillable = [
        'owner_id',
        'venue_id',
        'type',
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
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'cover_image',
        'images',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['cover_image_url', 'images_urls'];

    /**
     * Get the full URL for the venue request's cover image.
     *
     * @return string|null
     */
    public function getCoverImageUrlAttribute()
    {
        return $this->cover_image ? Storage::url($this->cover_image) : null;
    }

    /**
     * Get the full URLs for the venue request's additional images.
     *
     * @return array
     */
    public function getImagesUrlsAttribute()
    {
        if (empty($this->images)) return [];
        return collect($this->images)->map(fn ($imagePath) => Storage::url($imagePath))->toArray();
    }

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
