<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Point extends Model
{
    use HasUuids;

    protected $table = 'points';

    protected $fillable = [
        'id',
        'name',
        'status',
        'price_point',
        'range_point',
        'point',
        'description',
    ];

    public $incrementing = false;
    protected $keyType = 'string';
}