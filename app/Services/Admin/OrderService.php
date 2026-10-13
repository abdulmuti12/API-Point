<?php

namespace App\Services\Admin;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Point;
use App\Models\RedeemPoint;
use App\Services\AuditService;
use App\Models\MasterCity;
use App\Models\MasterDistrict;
use App\Models\MasterProvince;
use App\Models\MasterSubdistrict;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OrderService
{
    /**
     * Ambil daftar order untuk admin, dengan filter & pagination.
     */
    public function index(array $filters, int $page = 1): LengthAwarePaginator
    {
        $query = Order::whereHas('transaction', function ($q) {
            $q->where('status', 'success');
        });

        if (!empty($filters['order_code'])) {
            $query = $query->where('order_code', 'like', '%' . $filters['order_code'] . '%');
        }

        return $query->orderBy('id', 'desc')->paginate(10, ['*'], 'page', $page);
    }

    /**
     * Buat order baru (input transaksi admin).
     * Return:
     *  - ['order' => Order] bila sukses
     *  - ['error' => ..., 'errors' => ...] bila validasi gagal
     */
    public function store(array $request): array
    {
        $validator = Validator::make($request, [
            'customer_id'      => 'required|exists:customers,id',
            'grand_total'      => 'required|numeric|min:0',
            'order_number'     => 'nullable|string|max:50|unique:orders,order_number',
            'status'           => 'nullable|in:processing,shipped,delivered,success,cancelled',
            'recipient_name'   => 'nullable|string|max:255',
            'phone'            => 'nullable|string|max:20',
            'shipping_address' => 'nullable|string',
            'province_id'      => 'nullable|exists:master_province,prov_id',
            'city_id'          => 'nullable|exists:master_city,city_id',
            'district_id'      => 'nullable|exists:master_district,dis_id',
            'subdistrict_id'   => 'nullable|exists:master_subdistrict,subdis_id',
            'postal_code'      => 'nullable|string|max:10',
            'subtotal'         => 'nullable|numeric|min:0',
            'shipping_cost'    => 'nullable|numeric|min:0',
            'notes'            => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return ['error' => 'Validasi gagal', 'errors' => $validator->errors()];
        }

        // Validate items if provided
        if (!empty($request['items']) && is_array($request['items'])) {
            $itemErrors = [];
            foreach ($request['items'] as $index => $item) {
                $itemValidator = Validator::make($item, [
                    'name'     => 'required|string|max:255',
                    'quantity' => 'required|integer|min:1',
                    'price'    => 'required|numeric|min:0',
                ]);
                if ($itemValidator->fails()) {
                    foreach ($itemValidator->errors()->messages() as $field => $messages) {
                        foreach ($messages as $msg) {
                            $itemErrors[] = "Item #" . ($index + 1) . " $field: $msg";
                        }
                    }
                }
            }
            if (!empty($itemErrors)) {
                return ['error' => 'Validasi item gagal', 'errors' => $itemErrors];
            }
        }

        $data = $request;
        $data['customer_id'] = $data['customer_id'];
        $data['grand_total'] = (float) $data['grand_total'];
        $data['subtotal']    = isset($data['subtotal']) ? (float) $data['subtotal'] : (float) $data['grand_total'];
        $data['shipping_cost']= isset($data['shipping_cost']) ? (float) $data['shipping_cost'] : 0;

        // Auto generate order_number jika tidak diisi
        if (empty($data['order_number'])) {
            $data['order_number'] = 'ORD-' . date('Ymd') . '-' . strtoupper(Str::random(6));
        }

        // Auto fill address name jika tidak diisi
        if (empty($data['recipient_name'])) {
            $customer = Customer::find($data['customer_id']);
            $data['recipient_name'] = $customer ? $customer->full_name : 'Unknown';
        }

        // Auto fill province_name, city_name, dll
        if (!empty($data['province_id'])) {
            $prov = MasterProvince::find($data['province_id']);
            if ($prov) $data['province_name'] = $prov->name;
        }
        if (!empty($data['city_id'])) {
            $city = MasterCity::find($data['city_id']);
            if ($city) $data['city_name'] = $city->name;
        }
        if (!empty($data['district_id'])) {
            $dist = MasterDistrict::find($data['district_id']);
            if ($dist) $data['district_name'] = $dist->name;
        }
        if (!empty($data['subdistrict_id'])) {
            $sub = MasterSubdistrict::find($data['subdistrict_id']);
            if ($sub) $data['subdistrict_name'] = $sub->name;
        }

        $data['status'] = $data['status'] ?? 'processing';

        DB::beginTransaction();
        try {
            $order = Order::create($data);

            // Create order items if provided
            if (!empty($request['items']) && is_array($request['items'])) {
                foreach ($request['items'] as $item) {
                    $itemData = [
                        'order_id'      => $order->id,
                        'name'          => $item['name'] ?? '',
                        'quantity'      => (int) ($item['quantity'] ?? 1),
                        'price'         => (int) ($item['price'] ?? 0),
                        'category_id'   => !empty($item['category_id']) ? (int) $item['category_id'] : null,
                        'brand_id'      => !empty($item['brand_id']) ? (int) $item['brand_id'] : null,
                    ];
                    $itemData['product_name'] = $item['name'];
                    if (!empty($item['image'])) {
                        $itemData['image'] = $item['image'];
                    }
                    if (!empty($item['variant_name'])) {
                        $itemData['variant_name'] = $item['variant_name'];
                    }
                    OrderItem::create($itemData);
                }
            }

            DB::commit();

            // Audit: order baru dibuat oleh admin
            $customer = Customer::find($data['customer_id']);
            app(AuditService::class)->log(
                action:      'order.created',
                entityType:  'order',
                entityId:    (string) $order->id,
                description: "Order {$order->order_number} dibuat untuk customer \"{$customer->full_name}\" ({$order->grand_total})",
            );

            return ['order' => $order];
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Failed to create order', ['customer_id' => $data['customer_id'], 'exception' => $e]);
            return ['error' => 'Server Error', 'errors' => 'Terjadi kesalahan pada server'];
        }
    }

    /**
     * Ambil daftar order untuk halaman customer admin (filter + pagination).
     * Return: ['data' => [...], 'meta' => [...], 'links' => [...]]
     */
    public function getOrders(array $filters, int $page = 1, int $perPage = 10): array
    {
        $query = Order::with(['customer', 'items']);

        if (!empty($filters['order_number'])) {
            $query->where('order_number', 'like', '%' . $filters['order_number'] . '%');
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['customer_name'])) {
            $keyword = $filters['customer_name'];
            $query->whereHas('customer', function ($q) use ($keyword) {
                $q->where('name', 'like', '%' . $keyword . '%')
                  ->orWhere('full_name', 'like', '%' . $keyword . '%')
                  ->orWhere('email', 'like', '%' . $keyword . '%');
            });
        }

        $collection = $query->orderBy('id', 'desc')->paginate($perPage, ['*'], 'page', $page);
        $formatted = collect($collection->items())->map(function ($order) {
            return $this->formatOrderSummary($order);
        });

        return [
            'data' => $formatted->values(),
            'meta' => [
                'current_page' => $collection->currentPage(),
                'last_page'    => $collection->lastPage(),
                'per_page'     => $collection->perPage(),
                'total'        => $collection->total(),
            ],
            'links' => [
                'first_page_url' => $collection->url(1),
                'last_page_url'  => $collection->url($collection->lastPage()),
                'prev_page_url'  => $collection->previousPageUrl(),
                'next_page_url'  => $collection->nextPageUrl(),
            ],
        ];
    }

    /**
     * Detail order untuk admin.
     * Return:
     *  - array berisi data order detail bila ditemukan
     *  - null bila order tidak ada
     */
    public function getOrderDetail(int $id): ?array
    {
        $order = Order::with(['customer', 'items' => function ($q) {
                $q->with(['category', 'brand']);
            }, 'transaction'])
            ->find($id);

        if (!$order) {
            return null;
        }

        return $this->formatOrderDetail($order);
    }

    private function formatOrderSummary(Order $order): array
    {
        $totalItems = (int) $order->items->sum('quantity');
        $firstItem  = $order->items->first();

        $pointRule = Point::whereRaw("LOWER(status) = 'active'")->first();
        $customerId = $order->customer->id;

        // Total seluruh order delivered/success customer ini
        $totalCustomerTransaction = (int) Order::where('customer_id', $customerId)
            ->whereIn('status', ['delivered', 'success'])
            ->sum('grand_total');

        // Point per customer (berbasis total akumulasi)
        $customerPoints = $pointRule
            ? intdiv($totalCustomerTransaction, (int) $pointRule->range_point) * (int) $pointRule->point
            : 0;

        // Point sebelum order ini (untuk mengetahui berapa point baru dari order ini)
        $totalWithoutThisOrder = $totalCustomerTransaction - (in_array($order->status, ['delivered','success']) ? (int) $order->grand_total : 0);
        $pointsBefore = $pointRule
            ? intdiv($totalWithoutThisOrder, (int) $pointRule->range_point) * (int) $pointRule->point
            : 0;
        $pointsDelta = max(0, $customerPoints - $pointsBefore);

        // Ambil point yang sudah dicatat di DB
        $redeemPoint = RedeemPoint::where('customer_id', $customerId)
            ->where('point_id', $pointRule ? $pointRule->id : null)
            ->first();
        $recordedPoints = $redeemPoint ? (int) $redeemPoint->total_point_earned : 0;

        return [
            'id'              => (int) $order->id,
            'order_number'    => $order->order_number,
            'date'            => $order->created_at ? $order->created_at->toIso8601String() : null,
            'status'          => $order->status,

            // Customer info
            'customer'        => [
                'id'           => (int) $order->customer->id,
                'name'         => $order->customer->name,
                'full_name'    => $order->customer->full_name,
                'email'        => $order->customer->email,
                'phone_number' => $order->customer->phone_number,
            ],

            // Total nominal
            'subtotal'        => (int) $order->subtotal,
            'shipping_cost'   => (int) $order->shipping_cost,
            'grand_total'     => (int) $order->grand_total,

            // Item stats
            'total_items'     => $totalItems,
            'item_count'      => $order->items->count(),

            // Preview item pertama
            'first_item'      => $firstItem ? [
                'product_name' => $firstItem->product_name,
                'variant_name' => $firstItem->variant_name,
                'image'        => $firstItem->image,
            ] : null,

            // Points info
            'customer_total_transaction' => $totalCustomerTransaction,
            'customer_points'            => $customerPoints,
            'order_points_delta'         => $pointsDelta,
            'recorded_points'            => $recordedPoints,
            'point_rule'                 => $pointRule ? [
                'name'        => $pointRule->name,
                'range_point' => (int) $pointRule->range_point,
                'point'       => (int) $pointRule->point,
            ] : null,
        ];
    }

    private function formatOrderDetail(Order $order): array
    {
        $items = $order->items->map(function ($item) {
            return [
                'id'           => (int) $item->id,
                'product_id'   => (int) $item->product_id,
                'product_name' => $item->product_name,
                'name'         => $item->name ?? $item->product_name,
                'variant_name' => $item->variant_name,
                'quantity'     => (int) $item->quantity,
                'price'        => (int) $item->price,
                'subtotal'     => (int) ($item->quantity * $item->price),
                'image'        => $item->image,
                'category'     => $item->category ? [
                    'id'   => (int) $item->category->id,
                    'name' => $item->category->name,
                ] : null,
                'brand'        => $item->brand ? [
                    'id'   => (int) $item->brand->id,
                    'name' => $item->brand->name,
                ] : null,
            ];
        })->values();

        return [
            'id'           => (int) $order->id,
            'order_number' => $order->order_number,
            'date'         => $order->created_at ? $order->created_at->toIso8601String() : null,
            'status'       => $order->status,
            'notes'        => $order->notes,

            // Customer info lengkap
            'customer'     => [
                'id'           => (int) $order->customer->id,
                'name'         => $order->customer->name,
                'full_name'    => $order->customer->full_name,
                'email'        => $order->customer->email,
                'phone_number' => $order->customer->phone_number,
                'avatar'       => $order->customer->avatar,
            ],

            // Shipping info
            'shipping'     => [
                'recipient_name'   => $order->recipient_name,
                'phone'            => $order->phone,
                'shipping_address' => $order->shipping_address,
                'province_id'      => $order->province_id,
                'province_name'    => $order->province_name,
                'city_id'          => $order->city_id,
                'city_name'        => $order->city_name,
                'district_id'      => $order->district_id,
                'district_name'    => $order->district_name,
                'subdistrict_id'   => $order->subdistrict_id,
                'subdistrict_name' => $order->subdistrict_name,
                'postal_code'      => $order->postal_code,
            ],

            // Semua item yang dibeli
            'items'        => $items,

            // Ringkasan total
            'totals'       => [
                'subtotal'      => (int) $order->subtotal,
                'shipping_cost' => (int) $order->shipping_cost,
                'grand_total'   => (int) $order->grand_total,
                'total_items'   => (int) $items->sum('quantity'),
                'item_count'    => $items->count(),
            ],

            // Transaction info (jika ada)
            'transaction'  => $order->transaction ? [
                'id'                => (int) $order->transaction->id,
                'transaction_code'  => $order->transaction->transaction_code,
                'total_payment'     => (int) $order->transaction->total_payment,
                'status'            => $order->transaction->status,
                'note'              => $order->transaction->note,
            ] : null,

            // Points info (berbasis total akumulasi seluruh transaksi customer)
            'customer_total_transaction' => (function () use ($order) {
                return (int) Order::where('customer_id', $order->customer_id)
                    ->whereIn('status', ['delivered', 'success'])
                    ->sum('grand_total');
            })(),
            'customer_points'            => (function () use ($order) {
                $rule = Point::whereRaw("LOWER(status) = 'active'")->first();
                if (!$rule) return 0;
                $total = (int) Order::where('customer_id', $order->customer_id)
                    ->whereIn('status', ['delivered', 'success'])
                    ->sum('grand_total');
                return intdiv($total, (int) $rule->range_point) * (int) $rule->point;
            })(),
            'order_points_delta'         => (function () use ($order) {
                $rule = Point::whereRaw("LOWER(status) = 'active'")->first();
                if (!$rule || !in_array($order->status, ['delivered', 'success'])) return 0;
                $totalAll = (int) Order::where('customer_id', $order->customer_id)
                    ->whereIn('status', ['delivered', 'success'])
                    ->sum('grand_total');
                $withoutThis = $totalAll - (int) $order->grand_total;
                $before = intdiv($withoutThis, (int) $rule->range_point) * (int) $rule->point;
                $after  = intdiv($totalAll,   (int) $rule->range_point) * (int) $rule->point;
                return max(0, $after - $before);
            })(),
            'recorded_points'            => (function () use ($order) {
                $rule = Point::whereRaw("LOWER(status) = 'active'")->first();
                if (!$rule) return 0;
                $rp = RedeemPoint::where('customer_id', $order->customer_id)
                    ->where('point_id', $rule->id)
                    ->first();
                return $rp ? (int) $rp->total_point_earned : 0;
            })(),
            'point_rule'                 => (function () {
                $rule = Point::whereRaw("LOWER(status) = 'active'")->first();
                return $rule ? [
                    'name'        => $rule->name,
                    'range_point' => (int) $rule->range_point,
                    'point'       => (int) $rule->point,
                ] : null;
            })(),
        ];
    }
}
