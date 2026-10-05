<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class GiftClaim extends Model
{
    use HasUuids;

    protected $table = 'gift_claims';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'customer_id',
        'gift_id',
        'redeem_point_id',
        'approved_by',
        'required_point',
        'customer_point_at_request',
        'status',
        'notes',
        'admin_note',
        'approved_at',
        'completed_at',
    ];

    protected $casts = [
        'required_point'             => 'integer',
        'customer_point_at_request'  => 'integer',
        'approved_at'                => 'datetime',
        'completed_at'               => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function gift()
    {
        return $this->belongsTo(Gift::class, 'gift_id');
    }

    public function redeemPoint()
    {
        return $this->belongsTo(RedeemPoint::class, 'redeem_point_id');
    }

    public function approver()
    {
        return $this->belongsTo(\App\Models\Admin::class, 'approved_by');
    }
}