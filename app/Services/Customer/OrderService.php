<?php

namespace App\Services\Customer;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Point;

class OrderService
{
    /**
     * Ambil semua order customer (list view) dengan preview point.
     */
    public function listForCustomer(Customer $customer): array
    {
        $orders = Order::with('items')
            ->where('customer_id', $customer->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $pointRule = Point::where('status', 'active')->first();

        return $orders->map(function ($order) use ($pointRule) {
            return $this->formatOrder($order, false, $pointRule);
        })->values()->all();
    }

    /**
     * Ambil detail 1 order milik customer.
     * Return null bila order tidak ada / bukan milik customer.
     */
    public function detailForCustomer(Customer $customer, int $id): ?array
    {
        $order = Order::with(['items', 'transaction'])
            ->where('customer_id', $customer->id)
            ->where('id', $id)
            ->first();

        if (!$order) {
            return null;
        }

        $pointRule = Point::where('status', 'active')->first();
        return $this->formatOrder($order, true, $pointRule);
    }

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
            })->values()->all(),
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
            $formatted['notes']         = $order->notes;
            $formatted['subtotal']      = (int) $order->subtotal;
            $formatted['shipping_cost'] = (int) $order->shipping_cost;
        }

        return $formatted;
    }
}
