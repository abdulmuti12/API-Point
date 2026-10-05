<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Address;
use Illuminate\Http\Request;

class AddressController extends BaseController
{
    private $model;
    private $route = 'address';
    Public $title="Address";

    public function __construct(Address $model)
    {
        $this->model = $model;
    }
}
