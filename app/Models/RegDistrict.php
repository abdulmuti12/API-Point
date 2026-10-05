<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegDistrict extends Model
{
   protected $table = 'reg_districs';
   protected $fillable = [
      'id',
      'regency_id',
      'name',
   ];
    
}
