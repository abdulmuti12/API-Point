<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\EmailSubscriber;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class EmailSubscriberController extends BaseController
{
    private $model;
    private $route = 'email_subscribers';
    Public $title="EmailSubscriber";

    public function __construct(EmailSubscriber $model)
    {
        $this->model = $model;
    }

    public function store(Request $request)
    { 
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:email_subscribers,email',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $emailSubscriber = $this->model->create([
           'email' => $request->email,
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Email Sudah Ada',
            'data'    => $emailSubscriber
        ], 201);
    }
}


