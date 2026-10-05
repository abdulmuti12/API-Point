<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Brand extends Model
{
     protected $table = 'brands';

    protected $fillable = ['id','name', 'description', 'image', 'note','country_of_origin', 'created_at', 'updated_at', 'link'];
}
