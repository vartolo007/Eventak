<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OtpCode extends Model
{
    use HasFactory;

    // الحقول المسموح للكود حقن البيانات بداخلها مباشرة
    protected $fillable = [
        'user_id',
        'code',
        'expires_at',
        'is_used'
    ];

    // علاقة عكسية: الرمز ينتمي لمستخدم واحد (اختياري ولكن احترافي)
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
