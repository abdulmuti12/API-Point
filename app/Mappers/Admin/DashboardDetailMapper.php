<?php

namespace App\Mappers\Admin;
use App\Models\Admin;

class DashboardDetailMapper
{

    private $user;

    public function __construct(Admin $user)
    {
        $this->user = $user;
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
            'id' => $this->user->id,
            'name' => $this->user->name,
            'email' => $this->user->email,
            'role' => $this->user->roles[0]->name ?? null,
            'created_at' => $this->user->created_at,
            'updated_at' => $this->user->updated_at,
        ];
    }

}