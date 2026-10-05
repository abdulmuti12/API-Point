<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterSubdistrict extends Model
{
    protected $table = 'master_subdistrict';
    protected $primaryKey = 'subdis_id';
    public $timestamps = false;
    public $incrementing = false;
    protected $keyType = 'int';

    protected $fillable = ['subdis_id', 'subdis_name', 'dis_id'];

    public function district()
    {
        return $this->belongsTo(MasterDistrict::class, 'dis_id', 'dis_id');
    }

    public function postalCodes()
    {
        return $this->hasMany(MasterPostalCode::class, 'subdis_id', 'subdis_id');
    }
}
