<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Admin extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'image',
        'name',
        'email',
        'password',
        'role_id',
        'status',
        'facebook',
        'x_url',
        'linkedin',
        'whatsapp',
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
            'password' => 'hashed',
        ];
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function hasAccess($config_permession)
    {
        $role = $this->role;
        if (!$role) {
            return false;
        }
        foreach (json_decode($role->permession) as  $permession) {
            if ($config_permession == $permession ?? false) {
                return true;
            }
        }
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
