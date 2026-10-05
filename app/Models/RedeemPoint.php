<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class RedeemPoint extends Model
{
    use HasUuids;

    protected $table = 'redeem_points';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'customer_id',
        'point_id',
        'total_transaction',
        'total_point_earned',
        'total_point_claimed',
        'total_point_active',
        'total_point_closed',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function point()
    {
        return $this->belongsTo(Point::class, 'point_id');
    }

    public function histories()
    {
        return $this->hasMany(RedeemHistory::class, 'redeem_point_id');
    }
}