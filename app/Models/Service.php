<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;
use App\Traits\AutoTranslatable;

class Service extends Model
{
    use HasFactory, HasTranslations, AutoTranslatable;

    public array $translatable = ['name', 'description'];

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

     /**
     * Accessor لتعديل روابط الصور لتظهر كاملة
     *
     * @param  string|null  $value
     * @return array
     */
    public function getImagesAttribute($value)
    {
        // إذا لم تكن هناك صور، أرجع مصفوفة فارغة
        if (empty($value)) {
            return [];
        }

        // فك ترميز JSON إذا كانت الصور مخزنة كـ JSON string
        $imagesArray = is_string($value) ? json_decode($value, true) : $value;

        // تأكد أنها مصفوفة
        if (!is_array($imagesArray)) {
            return [];
        }

        // معالجة كل مسار صورة
        return array_map(function ($path) {
            // إذا كان المسار يبدأ بـ 'http' (رابط خارجي)، اتركه كما هو
            if (filter_var($path, FILTER_VALIDATE_URL)) {
                return $path;
            }
            // وإلا، قم بإنشاء الرابط الكامل باستخدام asset()
            // asset() ستستخدم APP_URL من ملف .env
            return asset('storage/' . $path);
        }, $imagesArray);
    }

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
