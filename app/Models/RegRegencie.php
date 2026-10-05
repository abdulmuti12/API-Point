<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegRegencie extends Model
{
   protected $table = 'reg_regencies';
   protected $fillable = [
      'id',
      'province_id',
      'name',
   ];
   public function province()
   {
      return $this->belongsTo(RegProvince::class);
   }
   public function regDistricts()
   {
      return $this->hasMany(RegDistrict::class, 'reg_regency_id');
   }
}
