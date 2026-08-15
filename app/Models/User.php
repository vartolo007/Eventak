<?php
namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'avatar',
        'phone',
        'password',
        'role',
        'otp_code',
        'otp_expires_at',
        'email_verified_at',
        'phone_verified_at',
        'vendor_category_id',
        'fcm_token',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    // علاقة المستخدم بـ المناسبات كزبون
    public function events()
    {
        return $this->hasMany(Event::class, 'customer_id');
    }

    // علاقة المستخدم بـ الصالات كصاحب صالة
    public function venues()
    {
        return $this->hasMany(Venue::class, 'owner_id');
    }

    // علاقة المستخدم بـ الخدمات كمورد
    public function services()
    {
        return $this->hasMany(Service::class, 'vendor_id');
    }
}
