<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Address extends Model
{
    protected $fillable = [
        'id',
        'user_id',
        'recipient_name',
        'phone',
        'province_id',
        'province_name',
        'city_id',
        'city_name',
        'district_id',
        'district_name',
        'subdistrict_id',
        'subdistrict_name',
        'postal_code',
        'address_line',
        'is_default',
    ];


    public function province()
    {
        return $this->belongsTo(MasterProvince::class, 'province_id');
    }

    public function city()
    {
        return $this->belongsTo(MasterCity::class, 'city_id');
    }

    public function district()
    {
        return $this->belongsTo(MasterDistrict::class, 'district_id');
    }

    public function subdistrict()
    {
        return $this->belongsTo(MasterSubdistrict::class, 'subdistrict_id');
    }

}

