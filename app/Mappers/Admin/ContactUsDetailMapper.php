<?php

namespace App\Mappers\Admin;
use App\Models\ContactUs;

class ContactUsDetailMapper
{
    private $data;

    public function __construct(ContactUs $data)
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
            'email' => $this->data->email,
            'phone_number' => $this->data->phone_number,
            'description'  => $this->data->description,
            'created_at' => $this->data->created_at,
            'updated_at' => $this->data->updated_at,
        ];
    }

}

