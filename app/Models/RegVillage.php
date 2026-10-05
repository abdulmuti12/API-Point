<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegVillage extends Model
{
   protected $table = 'reg_villages';
   protected $fillable = [
      'id',
      'district_id',
      'name',
   ];   
}
