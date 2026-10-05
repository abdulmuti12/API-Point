<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Logo extends Model
{
     protected $table = 'logos';

    protected $fillable = ['id', 'description', 'image', 'is_active'];

     protected $casts = [
        'is_active' => 'boolean',
    ];

         public static function getActiveLogo()
        {
            return self::where('is_active', true)->first();
        }
}
   