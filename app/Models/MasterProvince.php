<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterProvince extends Model
{
    protected $table = 'master_province';
    protected $primaryKey = 'prov_id';
    public $timestamps = false;
    public $incrementing = false;
    protected $keyType = 'int';

    protected $fillable = ['prov_id', 'prov_name'];

    public function cities()
    {
        return $this->hasMany(MasterCity::class, 'prov_id', 'prov_id');
    }
}
