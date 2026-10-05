<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $table = 'category';

    protected $fillable = ['id','name', 'description', 'image', 'note', 'created_at', 'updated_at'];
   
}
