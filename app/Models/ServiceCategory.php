<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;
use App\Traits\AutoTranslatable;

class ServiceCategory extends Model
{
    use HasFactory, HasTranslations, AutoTranslatable;

    public array $translatable = ['name', 'description'];

    protected $fillable = ['name', 'description'];

    // علاقة التصنيف بالخدمات
    public function services()
    {
        return $this->hasMany(Service::class, 'category_id');
    }
}
