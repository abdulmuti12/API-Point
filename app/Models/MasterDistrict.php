<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterDistrict extends Model
{
    protected $table = 'master_district';
    protected $primaryKey = 'dis_id';
    public $timestamps = false;
    public $incrementing = false;
    protected $keyType = 'int';

    protected $fillable = ['dis_id', 'dis_name', 'city_id'];

    public function city()
    {
        return $this->belongsTo(MasterCity::class, 'city_id', 'city_id');
    }

    public function subdistricts()
    {
        return $this->hasMany(MasterSubdistrict::class, 'dis_id', 'dis_id');
    }
}
