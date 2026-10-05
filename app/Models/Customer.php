<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class Customer extends Authenticatable implements JWTSubject
{
    use HasUuids, Notifiable;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'name',
        'full_name',
        'email',
        'phone_number',
        'password',
        'status',
        'verify',
        'time',
        'email_verified_at',
        'provider',
        'provider_id',
        'avatar',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function address()
    {
        return $this->belongsTo(Address::class,'id','user_id');
    }

    public function addressTexts()
    {
        return $this->hasMany(AddressText::class, 'customer_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'customer_id');
    }

    protected $casts = [
        'last_login_at' => 'datetime',
        'email_verified_at' => 'datetime',
    ];

    // JWTSubject interface methods
    public function getJWTIdentifier()
    {
        return $this->getKey(); // biasanya return id
    }

    public function getJWTCustomClaims()
    {
        return [];
    }
}
