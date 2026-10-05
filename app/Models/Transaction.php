<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
      protected $table = 'transaction';

    protected $fillable = [
        'id',
        'transaction_code',
        'order_id',
        'total_payment',
        'status',
        'note',
        'address_id',
    ];
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    
}
