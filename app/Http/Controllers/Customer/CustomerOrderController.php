<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Point;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

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
    public function getOrders(Request $request)
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

            $orders = Order::with('items')
                ->where('customer_id', $customer->id)
                ->orderBy('created_at', 'desc')
                ->get();

            $pointRule = Point::where('status', 'active')->first();

            $data = $orders->map(function ($order) use ($pointRule) {
                return $this->formatOrder($order, false, $pointRule);
            });

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
    public function getOrderDetail(Request $request, $id)
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

            $order = Order::with(['items', 'transaction'])
                ->where('customer_id', $customer->id)
                ->where('id', $id)
                ->first();

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order tidak ditemukan',
                    'status' => 404,
                ], 404);
            }

            $pointRule = Point::where('status', 'active')->first();
            $data = $this->formatOrder($order, true, $pointRule);

            return $this->sendResponse($data, 'Detail order berhasil diambil');
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
                'status' => 500,
            ], 500);
        }
    }

    /**
     * Format order + items menjadi response sesuai kontrak frontend.
     *
     * @param  Order  $order
     * @param  bool   $includeShipping  true jika untuk detail (ikutkan alamat)
     * @return array
     */
    private function formatOrder(Order $order, bool $includeShipping = false, ?Point $pointRule = null): array
    {
        $totalItems = (int) $order->items->sum('quantity');

        $pointEarned = 0;
        $rangePoint = 0;
        $pointValue = 0;
        $pointQualifies = false;
        if ($pointRule && in_array($order->status, ['delivered', 'success'], true)) {
            $rangePoint = (int) $pointRule->range_point;
            $pointValue = (int) $pointRule->point;
            $pointEarned = $rangePoint > 0
                ? intdiv((int) $order->grand_total, $rangePoint) * $pointValue
                : 0;
            $pointQualifies = $pointEarned > 0;
        }

        $formatted = [
            'id'              => (int) $order->id,
            'order_number'    => $order->order_number,
            'date'            => $order->created_at ? $order->created_at->toIso8601String() : null,
            'status'          => $order->status,
            'total'           => (int) $order->grand_total,
            'total_items'     => $totalItems,
            'point_earned'    => $pointEarned,
            'point_qualifies' => $pointQualifies,
            'items'           => $order->items->map(function ($item) {
                return [
                    'product_id'   => (int) $item->product_id,
                    'product_name' => $item->product_name,
                    'variant_name' => $item->variant_name,
                    'quantity'     => (int) $item->quantity,
                    'price'        => (int) $item->price,
                    'image'        => $item->image,
                ];
            })->values(),
        ];

        if ($includeShipping) {
            $formatted['shipping'] = [
                'recipient_name' => $order->recipient_name,
                'phone'          => $order->phone,
                'address_line'   => $order->shipping_address,
                'province'       => $order->province_name,
                'city'           => $order->city_name,
                'district'       => $order->district_name,
                'subdistrict'    => $order->subdistrict_name,
                'postal_code'    => $order->postal_code,
            ];
            $formatted['notes'] = $order->notes;
            $formatted['subtotal']      = (int) $order->subtotal;
            $formatted['shipping_cost'] = (int) $order->shipping_cost;
        }

        return $formatted;
    }
}