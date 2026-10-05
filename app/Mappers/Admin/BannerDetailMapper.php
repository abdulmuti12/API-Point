<?php
// filepath: /Applications/XAMPP/xamppfiles/htdocs/API_BACKEND_LAP/app/Mappers/Admin/BannerDetailMapper.php

namespace App\Mappers\Admin;

use App\Models\Banner;

class BannerDetailMapper
{
    protected $data;

    public function __construct(Banner $data)
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
            'title' => $this->data->title,
            'title2' => $this->data->title2,
            'title3' => $this->data->title3,
            'title4' => $this->data->title4,
            'title5' => $this->data->title5,
            'file' => $this->data->file ? asset('storage/' . $this->data->file) : null,
            'file2' => $this->data->file2 ? asset('storage/' . $this->data->file2) : null,
            'file3' => $this->data->file3 ? asset('storage/' . $this->data->file3) : null,
            'file4' => $this->data->file4 ? asset('storage/' . $this->data->file4) : null,
            'file5' => $this->data->file5 ? asset('storage/' . $this->data->file5) : null,
            'file6' => $this->data->file6 ? asset('storage/' . $this->data->file6) : null,
            'file7' => $this->data->file7 ? asset('storage/' . $this->data->file7) : null,
            'created_at' => $this->data->created_at,
            'updated_at' => $this->data->updated_at,
        ];
    }
}