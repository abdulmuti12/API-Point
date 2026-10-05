<?php

namespace App\Mappers\Admin;
use App\Models\Promotion;

class PromotionDetailMapper
{
    private $data;

    public function __construct(Promotion $data)
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
            'description' => $this->data->description,
            'note' => $this->data->note,
            'type' => $this->data->type,
            'brand' => $this->data->brand->name ?? null,
            'file' => $this->data->file ? asset('storage/' . $this->data->file) : null,
            'file2' => $this->data->file ? asset('storage/' . $this->data->file2) : null,
            'file3' => $this->data->file ? asset(path: 'storage/' . $this->data->file3) : null,
            'file4' => $this->data->file ? asset(path: 'storage/' . $this->data->file4) : null,
            'file5' => $this->data->file ? asset(path: 'storage/' . $this->data->file5) : null,
            'created_at' => $this->data->created_at,
            'updated_at' => $this->data->updated_at,
        ];
    }

}
