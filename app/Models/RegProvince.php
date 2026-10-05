<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegProvince extends Model
{
    protected $table = 'reg_provinces';
    
    protected $fillable = [ 
        'id',
        'name',
    ];  
}
