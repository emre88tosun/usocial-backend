<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens, HasRoles, Billable;

    protected function getDefaultGuardName(): string
    {
        return 'api';
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'roles',
        'gems',
        'pm_last_four',
        "pm_type",
        "stripe_id",
        "trial_ends_at"
    ];

    protected $appends = ['role', 'gem_data'];

    protected $with = [];

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


    public function getRoleAttribute()
    {
        return $this->roles->first()?->only(['id', 'name']);
    }

    public function gems()
    {
        return $this->hasOne(Gem::class);
    }

    public function influencer()
    {
        return $this->hasOne(Influencer::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function getGemDataAttribute()
    {
        if ($this->gems) {
            return [
                'id' => $this->gems->id,
                'amount' => $this->gems->gem_count,
            ];
        }

        return null;
    }
}
