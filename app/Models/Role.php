<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class Role extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function menus()
    {
        return $this->belongsToMany(Menu::class, 'role_menu');
    }

    public function admins()
    {
        return $this->belongsToMany(Admin::class, 'admin_role');
    }
}
