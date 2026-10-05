<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $table = 'orders';

    protected $fillable = [
        'id',
        'order_number',
        'customer_id',
        'recipient_name',
        'phone',
        'shipping_address',
        'province_id',
        'province_name',
        'city_id',
        'city_name',
        'district_id',
        'district_name',
        'subdistrict_id',
        'subdistrict_name',
        'postal_code',
        'subtotal',
        'shipping_cost',
        'grand_total',
        'status',
        'notes',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'subtotal' => 'integer',
        'shipping_cost' => 'integer',
        'grand_total' => 'integer',
    ];

    // Status yang diizinkan
    public const STATUSES = [
        'pending',
        'processing',
        'shipped',
        'delivered',
        'cancelled',
    ];

    /**
     * Relasi ke customer (pemilik order).
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /**
     * Relasi ke order items (1 order punya banyak item/produk).
     */
    public function items()
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    /**
     * Relasi ke transaction (1 order punya 0/1 transaction).
     */
    public function transaction()
    {
        return $this->hasOne(Transaction::class, 'order_id');
    }
}
