<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterPostalCode extends Model
{
    protected $table = 'master_postal_code';
    protected $primaryKey = 'postal_id';
    public $timestamps = false;
    public $incrementing = false;
    protected $keyType = 'int';

    protected $fillable = ['postal_id', 'subdis_id', 'dis_id', 'city_id', 'prov_id', 'postal_code'];

    public function subdistrict()
    {
        return $this->belongsTo(MasterSubdistrict::class, 'subdis_id', 'subdis_id');
    }

    public function district()
    {
        return $this->belongsTo(MasterDistrict::class, 'dis_id', 'dis_id');
    }

    public function city()
    {
        return $this->belongsTo(MasterCity::class, 'city_id', 'city_id');
    }

    public function province()
    {
        return $this->belongsTo(MasterProvince::class, 'prov_id', 'prov_id');
    }
}
