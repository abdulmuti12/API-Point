<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Promotion;

class PromotionCustomersController extends BaseController
{
    private $model;
    private $route = 'promotion';
    Public $title="Promotion";


    public function __construct(Promotion $model)
    {
        $this->model = $model;
    }


    public function index(Request $request)
    {
        $promotion = $this->model
            ->select('id', 'name', 'description', 'file')
            ->where('status', 'active')
            ->orderBy('id', 'desc')
            ->first();

        if ($promotion) {
            $promotion->file = $promotion->file ? asset('storage/' . $promotion->file) : null;
        }

        return $this->sendResponse($promotion, 'Success Load Data');
    }
}
