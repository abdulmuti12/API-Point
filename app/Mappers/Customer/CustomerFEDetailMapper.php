<?php

namespace App\Mappers\Customer;

use App\Models\Customer;

class CustomerFEDetailMapper
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
        $data['address']=$this->address();

        return $data;
    }

    public function general()
    {
        return [
            'id' => $this->data->id,
            'name' => $this->data->name,
            'email' => $this->data->email,
            'phone' => $this->data->phone_number,
            'created_at' => $this->data->created_at,
            'updated_at' => $this->data->updated_at,
        ];
    }

    public function address(){

        return [
            'provinsi' => @$this->data->address->provinces->name,
            'city'=>@$this->data->address->cities->name,
            'district'=>@$this->data->address->districts->name,
            'village'=>@$this->data->address->villages->name,
        ];
    }

}