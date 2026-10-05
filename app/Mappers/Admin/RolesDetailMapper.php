<?php

namespace App\Mappers\Admin;
use App\Models\Admin;
use App\Models\Role;

class RolesDetailMapper
{

    private $role;

    public function __construct(Role $role)
    {
        $this->role = $role;
    }

    public function data()
    {
        $data = [];
        $data['general']=$this->general();

        return $data;
    }

    public function general()
    {
        return [
            'id' => $this->role->id,
            'name' => $this->role->name,
            'created_at' => $this->role->created_at,
            'updated_at' => $this->role->updated_at,
            'menu' => $this->menu(),

        ];
    }
    public function menu()
    {
        return [
            'menu' => $this->role->menus->map(function ($menu) {
                return [
                    'id' => $menu->id,
                    'name' => $menu->name,
                    'route' => $menu->route,
                ];
            }),
        ];
    }

}