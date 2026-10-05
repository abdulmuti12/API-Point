<?php

namespace App\Mappers\Admin;
use App\Models\Customer;

class CustomerDetailMapper
{
    private $data;

    public function __construct(Customer $data)
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
            'full_name' => $this->data->full_name,
            'email' => $this->data->email,
            'phone_number' => $this->data->phone_number,
            'status' => $this->data->status,
            'time' => $this->data->time ?? null,
            'created_at' => $this->data->created_at,
            'updated_at' => $this->data->updated_at,

        ];
    }

}