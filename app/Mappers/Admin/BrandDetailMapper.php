<?php

namespace App\Mappers\Admin;
use App\Models\Brand;

class BrandDetailMapper
{
    private $data;

    public function __construct(Brand $data)
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
            'name' => $this->data->name,
            'image' => $this->data->image ? asset('storage/' . $this->data->image) : null,
            'note' => $this->data->note,
            'description' => $this->data->description,
            'created_at' => $this->data->created_at,
            'updated_at' => $this->data->updated_at,
            'link' => $this->data->link,

        ];
    }

}