<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\ContactUs;
use Faker\Provider\Base;
use Illuminate\Http\Request;
use App\Http\Resources\Admin\ContactUsResource;
use App\Mappers\Admin\ContactUsDetailMapper;
use Illuminate\Support\Facades\Validator;


class ContactUsCustomerController extends BaseController
{
    private $model;
    private $route = 'contact_us';
    Public $title="ContactUs"; 
    public function __construct(ContactUs $model)
    {
        $this->model = $model;
    }
     public function store(Request $request)
    {
       $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone_number' => 'nullable|string|max:20',
            'description' => 'nullable|string|max:1000',
       ]);

       if ($validator->fails()) {
            return $this->sendError('Validasi gagal', $validator->errors());
        }

        // Sanitize input to prevent XSS
        $data = [
            'name' => strip_tags($request->input('name')),
            'email' => filter_var($request->input('email'), FILTER_SANITIZE_EMAIL),
            'phone_number' => $request->input('phone_number') ? strip_tags($request->input('phone_number')) : null,
            'description' => strip_tags($request->input('description')),
        ];

        $contact_us = ContactUs::create($data);

       return $this->sendResponse($contact_us, 'Add Success');
    }
}
