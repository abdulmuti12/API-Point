<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    protected $table = 'promotions';

    protected $fillable = [
        'name',
        'description',
        'note',
        'type',
        'brand_id',
        'file',
        'file2',
        'file3',
        'file4',
        'file5',
        'status',

    ];
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
public function brand()
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }

}
