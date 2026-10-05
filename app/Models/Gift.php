<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Gift extends Model
{
    use HasUuids;

    protected $table = 'gifts';

    protected $fillable = [
        'id',
        'name',
        'total_point',
        'image',
        'description',
        'status',
    ];

    public $incrementing = false;
    protected $keyType = 'string';
}