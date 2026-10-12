<?php

namespace App\Http\Controllers\Customer;

use App\Services\Customer\GiftService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GiftCustomerController extends BaseController
{
    /**
     * GET /api/customers/gifts
     *
     * Response:
     * {
     *   "success": true,
     *   "data": {
     *     "total_points": 120,
     *     "gifts": [...],
     *     "stats": {...}
     *   }
     * }
     */
    public function index(Request $request, GiftService $service)
    {
        $customer = Auth::guard('customer')->user();

        if (!$customer) {
            return $this->sendError('Unauthorized', 'Silakan login terlebih dahulu', 401);
        }

        return $this->sendResponse(
            $service->indexForCustomer($customer),
            'Data gifts berhasil diambil'
        );
    }

    /**
     * POST /api/customers/gifts/claim
     * Body: { gift_id, notes? }
     * Customer request claim gift. Status awal = "waiting", belum potong point.
     * Point baru terpotong saat admin approve.
     */
    public function claim(Request $request, GiftService $service)
    {
        $customer = Auth::guard('customer')->user();

        if (!$customer) {
            return $this->sendError('Unauthorized', 'Silakan login terlebih dahulu', 401);
        }

        $result = $service->claimForCustomer($customer, $request->all());

        if (isset($result['error'])) {
            return $this->sendError($result['error'], $result['message']);
        }

        return $this->sendResponse(
            $result['data'],
            'Request claim berhasil dibuat, menunggu approval admin'
        );
    }

    /**
     * GET /api/customers/gifts/claims
     * List semua claim customer (history: waiting, approved, rejected, completed)
     */
    public function claims(Request $request, GiftService $service)
    {
        $customer = Auth::guard('customer')->user();

        if (!$customer) {
            return $this->sendError('Unauthorized', 'Silakan login terlebih dahulu', 401);
        }

        return $this->sendResponse(
            $service->claimsForCustomer($customer, $request->input('status')),
            'Data claim berhasil diambil'
        );
    }
}