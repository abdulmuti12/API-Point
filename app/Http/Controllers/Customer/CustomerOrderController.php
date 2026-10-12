<?php

namespace App\Http\Controllers\Customer;

use Illuminate\Http\Request;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use App\Services\Customer\OrderService;

class CustomerOrderController extends BaseController
{
    private $model;
    private $route = 'orders';
    Public $title="Order";

    public function __construct(Order $model)
    {
        $this->model = $model;
    }

    /**
     * GET /api/customers/get-orders
     * Mengambil semua order milik customer yang sedang login,
     * lengkap dengan items-nya (relasi order_items).
     */
    public function getOrders(Request $request, OrderService $service)
    {
        try {
            $customer = Auth::guard('customer')->user();

            if (!$customer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Silakan login terlebih dahulu',
                    'status' => 401,
                ], 401);
            }

            $data = $service->listForCustomer($customer);

            return $this->sendResponse($data, 'Data order berhasil diambil');
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }

    /**
     * GET /api/customers/get-orders/{id}
     * Mengambil detail 1 order milik customer yang sedang login.
     */
    public function getOrderDetail(Request $request, $id, OrderService $service)
    {
        try {
            $customer = Auth::guard('customer')->user();

            if (!$customer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Silakan login terlebih dahulu',
                    'status' => 401,
                ], 401);
            }

            $data = $service->detailForCustomer($customer, (int) $id);

            if (!$data) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order tidak ditemukan',
                    'status' => 404,
                ], 404);
            }

            return $this->sendResponse($data, 'Detail order berhasil diambil');
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }
}
