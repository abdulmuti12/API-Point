<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    protected $table = 'banner';

    protected $fillable = [
        'id',
        'name',
        'description',
        'note',
        'type',
        'file',
        'file2',
        'file3',
        'file4',
        'file5',
        'file6',
        'file7',
        'note2',
        'note3',
        'note4',
        'note5',
        'title',
        'title2',
        'title3',
        'title4',
        'title5',
        'status',
        'created_at',
        'updated_at',
    ];
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    
}
