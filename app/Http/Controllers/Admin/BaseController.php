<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;


class BaseController extends Controller
{
    public function sendResponse($result, $message = null)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $result,
            'status'=>200,
        ], 200);
    }

    public function sendError($error, $message = null)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'error' => $error,
            'status'=>401,
        ], 401);
    }
}
