<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Transaction;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;


class CustomerTransactionController extends BaseController
{
    private $model;
    private $route = 'transactions';
    Public $title="Transaction";

    public function __construct(Transaction $model)
    {
        $this->model = $model;
    }
}
