<?php

namespace App\Mappers\Admin;
use App\Models\Logo;

class LogoDetailMapper
{
    private $data;

    public function __construct(Logo $data)
    {
        $this->data = $data;
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
            'id' => $this->data->id,
            'description' => $this->data->description,
            'image' => $this->data->image ? asset('storage/' . $this->data->image) : null,
            'is_active'   => (bool) $this->data->is_active,
        ];
    }

}