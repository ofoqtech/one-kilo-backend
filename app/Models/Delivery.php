<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Delivery extends Authenticatable
{
    use HasApiTokens, HasFactory ,Notifiable ;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */

    const STATUS_PENDING  = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'full_name',
        'phone',
        'email',
        'password',
        'fcm_token',
        'firebase_uid',
        'image',
        'vehicle_type',
        'vehicle_model',
        'vehicle_brand',
        'national_id_image',
        'license_image',
        'vehicle_license_image',
        'status',
        'login_status',
        'email_verified_at',
        'lat',
        'lng',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
    ];


    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function appNotifications()
    {
        return $this->morphMany(Notification::class, 'notifiable');
    }

    public function shifts()
    {
        return $this->morphMany(Shift::class, 'shiftable');
    }

    public function currentShift(): ?Shift
    {
        return $this->shifts()->open()->latest('started_at')->first();
    }

}
