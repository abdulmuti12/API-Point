<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class RedeemHistory extends Model
{
    use HasUuids;

    protected $table = 'redeem_histories';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'redeem_point_id',
        'customer_id',
        'point_id',
        'type',
        'point_amount',
        'point_balance',
        'status',
        'description',
    ];

    public function redeemPoint()
    {
        return $this->belongsTo(RedeemPoint::class, 'redeem_point_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function point()
    {
        return $this->belongsTo(Point::class, 'point_id');
    }
}