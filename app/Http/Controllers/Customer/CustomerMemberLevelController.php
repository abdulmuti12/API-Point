<?php

namespace App\Http\Controllers\Customer;

use App\Services\Customer\MemberLevelService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerMemberLevelController extends BaseController
{
    public function show(Request $request, MemberLevelService $service)
    {
        $customer = Auth::guard('customer')->user();

        if (!$customer) {
            return $this->sendError('Unauthorized', 'Silakan login terlebih dahulu', 401);
        }

        return $this->sendResponse(
            $service->calculateForCustomer($customer),
            'Data member level berhasil diambil'
        );
    }
}