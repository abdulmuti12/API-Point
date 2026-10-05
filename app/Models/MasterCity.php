<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterCity extends Model
{
    protected $table = 'master_city';
    protected $primaryKey = 'city_id';
    public $timestamps = false;
    public $incrementing = false;
    protected $keyType = 'int';

    protected $fillable = ['city_id', 'city_name', 'prov_id'];

    public function province()
    {
        return $this->belongsTo(MasterProvince::class, 'prov_id', 'prov_id');
    }

    public function districts()
    {
        return $this->hasMany(MasterDistrict::class, 'city_id', 'city_id');
    }
}
