<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $table = 'order_items';

    protected $fillable = [
        'id',
        'order_id',
        'product_id',
        'product_name',
        'name',
        'variant_name',
        'quantity',
        'price',
        'image',
        'category_id',
        'brand_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'quantity' => 'integer',
        'price' => 'integer',
    ];

    /**
     * Subtotal item ini (quantity * price).
     */
    public function getSubtotalAttribute(): int
    {
        return (int) ($this->quantity * $this->price);
    }

    /**
     * Relasi ke order induk.
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * Relasi ke kategori (untuk input manual transaksi).
     */
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    /**
     * Relasi ke brand (untuk input manual transaksi).
     */
    public function brand()
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }
}
