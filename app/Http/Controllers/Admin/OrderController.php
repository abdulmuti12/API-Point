<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Customer;
use App\Models\Province;
use App\Models\City;
use App\Models\District;
use App\Models\Subdistrict;
use App\Models\Category;
use App\Models\Brand;
use App\Http\Resources\Admin\OrderResource;
use App\Models\RedeemHistory;
use App\Models\RedeemPoint;
use App\Models\Point;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class OrderController extends BaseController
{
   private $model;
    private $route = 'orders';
    Public $title="Order";

    public function __construct(Order $model)
    {
        $this->model = $model;
    }

    public function index(Request $request)
    {
        // $access = Auth::guard(name: 'api')->user(); // atau 'admins' sesuai guard kamu
        // if (!$access || !$access->hasAccessToMenu($this->route)) {
        //     return $this->sendError('Akses ditolak', 'Tidak memiliki akses ke menu ini', 403);
        // }

        $dataRequest=$request->all() ?? null;
        $page = $request->query('page') ?? 1;
        $raw = (bool) $request->query('raw') ?? false;

        // Hanya tampilkan order yang transaksinya berstatus success
        $data = $this->model->whereHas('transaction', function ($query) {
            $query->where('status', 'success');
        });

        if($dataRequest){

            if (isset($dataRequest['order_code'])) {
                $data = $data->where(function ($query) use ($dataRequest) {
                    $query->where('order_code', 'like', '%' . $dataRequest['order_code'] . '%');
                });
            }
        }
        $collection = $data->orderBy('id', 'desc')->paginate(10, ['*'], 'page', $page);
        $rawData = OrderResource::collection($collection);
        if ($raw) {
            $datas = $rawData;
        } else {
            $responses = $rawData->response()->getData(true);
            $links = $responses['links'];
            $dataLinks = [];
            unset($responses['links']);
            $meta = $responses['meta'];
            unset($responses['meta']);
            $datas = [
                'data' => $responses,
                'meta' => $meta,
                'links' => $links,
            ];
        }

        return $this->sendResponse($datas, 'Data berhasil diambil');
    }

    /**
     * POST /api/admins/order
     * Input transaksi baru — hanya butuh: customer_id + grand_total (+ opsional notes/recipient/address)
     * Mendukung items[] array untuk menambahkan item ke order:
     *   [
     *     { "name": "Sofa Minimalis", "category_id": 1, "brand_id": 2, "quantity": 2, "price": 1500000 },
     *     { "name": "Meja Kopi", "category_id": 1, "brand_id": 3, "quantity": 1, "price": 500000 },
     *   ]
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_id'      => 'required|exists:customers,id',
            'grand_total'      => 'required|numeric|min:0',
            'order_number'     => 'nullable|string|max:50|unique:orders,order_number',
            'status'           => 'nullable|in:processing,shipped,delivered,success,cancelled',
            'recipient_name'   => 'nullable|string|max:255',
            'phone'            => 'nullable|string|max:20',
            'shipping_address' => 'nullable|string',
            'province_id'      => 'nullable|exists:provinces,id',
            'city_id'          => 'nullable|exists:cities,id',
            'district_id'      => 'nullable|exists:districts,id',
            'subdistrict_id'   => 'nullable|exists:subdistricts,id',
            'postal_code'      => 'nullable|string|max:10',
            'subtotal'         => 'nullable|numeric|min:0',
            'shipping_cost'    => 'nullable|numeric|min:0',
            'notes'            => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validasi gagal', $validator->errors());
        }

        // Validate items if provided
        if ($request->filled('items') && is_array($request->items)) {
            $itemErrors = [];
            foreach ($request->items as $index => $item) {
                $itemValidator = Validator::make($item, [
                    'name'      => 'required|string|max:255',
                    'quantity'  => 'required|integer|min:1',
                    'price'     => 'required|numeric|min:0',
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
                return $this->sendError('Validasi item gagal', $itemErrors);
            }
        }

        $data              = $request->only([
            'customer_id', 'grand_total', 'order_number', 'status',
            'recipient_name', 'phone', 'shipping_address',
            'province_id', 'city_id', 'district_id', 'subdistrict_id',
            'postal_code', 'subtotal', 'shipping_cost', 'notes',
        ]);
        $data['customer_id'] = $data['customer_id'];
        $data['grand_total'] = (float) $data['grand_total'];
        $data['subtotal']    = isset($data['subtotal']) ? (float) $data['subtotal'] : (float) $data['grand_total'];
        $data['shipping_cost'] = isset($data['shipping_cost']) ? (float) $data['shipping_cost'] : 0;

        // Auto generate order_number jika tidak diisi
        if (empty($data['order_number'])) {
            $data['order_number'] = 'ORD-' . date('Ymd') . '-' . str_pad((int) Order::whereDate('created_at', today())->count() + 1, 3, '0', STR_PAD_LEFT);
        }

        // Auto fill address name jika tidak diisi
        if (empty($data['recipient_name'])) {
            $customer = Customer::find($data['customer_id']);
            $data['recipient_name'] = $customer ? $customer->full_name : 'Unknown';
        }

        // Auto fill province_name, city_name, dll
        if (!empty($data['province_id'])) {
            $prov = Province::find($data['province_id']);
            if ($prov) $data['province_name'] = $prov->name;
        }
        if (!empty($data['city_id'])) {
            $city = City::find($data['city_id']);
            if ($city) $data['city_name'] = $city->name;
        }
        if (!empty($data['district_id'])) {
            $dist = District::find($data['district_id']);
            if ($dist) $data['district_name'] = $dist->name;
        }
        if (!empty($data['subdistrict_id'])) {
            $sub = Subdistrict::find($data['subdistrict_id']);
            if ($sub) $data['subdistrict_name'] = $sub->name;
        }

        $data['status'] = $data['status'] ?? 'processing';

        $order = Order::create($data);

        // Create order items if provided
        if ($request->filled('items') && is_array($request->items)) {
            foreach ($request->items as $item) {
                $itemData = [
                    'order_id'       => $order->id,
                    'name'           => $item['name'] ?? '',
                    'quantity'       => (int) ($item['quantity'] ?? 1),
                    'price'          => (int) ($item['price'] ?? 0),
                    'category_id'    => !empty($item['category_id']) ? (int) $item['category_id'] : null,
                    'brand_id'       => !empty($item['brand_id']) ? (int) $item['brand_id'] : null,
                ];
                // Keep backwards compat: set product_name = name for list view compatibility
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

        return $this->sendResponse($order, 'Transaksi berhasil ditambahkan');
    }

    /**
     * GET /api/admins/get-customers
     * List customer untuk selectbox di halaman input transaksi
     */
    public function getCustomers(Request $request)
    {
        $page     = $request->query('page') ?? 1;
        $perPage  = $request->query('per_page') ?? 50;
        $keyword  = $request->query('keyword') ?? '';

        $query = Customer::query()->select('id', 'name', 'full_name', 'email', 'phone_number', 'status');

        if ($keyword) {
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', '%' . $keyword . '%')
                  ->orWhere('full_name', 'like', '%' . $keyword . '%')
                  ->orWhere('email', 'like', '%' . $keyword . '%')
                  ->orWhere('phone_number', 'like', '%' . $keyword . '%');
            });
        }

        $customers = $query->orderBy('name')->paginate($perPage, ['*'], 'page', $page);

        return $this->sendResponse($customers, 'Data customer berhasil diambil');
    }

    /**
     * GET /api/admins/get-categories
     * List semua category untuk selectbox di form input transaksi.
     */
    public function getCategories(Request $request)
    {
        try {
            $categories = Category::orderBy('name')->get(['id', 'name']);
            return $this->sendResponse($categories, 'Data category berhasil diambil');
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . 'Terjadi kesalahan pada server',
                'status' => 500,
            ], 500);
        }
    }

    /**
     * GET /api/admins/get-brands
     * List semua brand untuk selectbox di form input transaksi.
     */
    public function getBrands(Request $request)
    {
        try {
            $brands = Brand::orderBy('name')->get(['id', 'name']);
            return $this->sendResponse($brands, 'Data brand berhasil diambil');
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . 'Terjadi kesalahan pada server',
                'status' => 500,
            ], 500);
        }
    }

    /**
     * GET /api/admins/get-orders
     * Menampilkan semua order (untuk semua customer) lengkap dengan:
     *   - customer info (nama, email, phone)
     *   - total nominal (subtotal, shipping_cost, grand_total)
     *   - total quantity item
     *   - preview 1 item pertama (untuk list view)
     *
     * Support filter: ?order_number=xxx&status=xxx&customer_name=xxx
     */
    public function getOrders(Request $request)
    {
        try {
            $dataRequest = $request->all() ?? [];
            $page = $request->query('page') ?? 1;
            $perPage = $request->query('per_page') ?? 10;
            $raw = (bool) $request->query('raw') ?? false;

            $query = Order::with(['customer', 'items']);

            // Filter by order_number
            if (!empty($dataRequest['order_number'])) {
                $query->where('order_number', 'like', '%' . $dataRequest['order_number'] . '%');
            }

            // Filter by status
            if (!empty($dataRequest['status'])) {
                $query->where('status', $dataRequest['status']);
            }

            // Filter by customer name (nama, full_name, atau email)
            if (!empty($dataRequest['customer_name'])) {
                $keyword = $dataRequest['customer_name'];
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

            $datas = [
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

            if ($raw) {
                $datas = $formatted;
            }

            return $this->sendResponse($datas, 'Data order berhasil diambil');
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . 'Terjadi kesalahan pada server',
                'status' => 500,
            ], 500);
        }
    }

    /**
     * GET /api/admins/get-orders/{id}
     * Menampilkan detail 1 order lengkap dengan:
     *   - customer info (nama, email, phone)
     *   - shipping info (alamat tujuan)
     *   - semua item yang dibeli (product_name, variant_name, qty, price, image, subtotal)
     *   - ringkasan total (subtotal, shipping_cost, grand_total)
     *   - transaction info (jika ada)
     */
    public function getOrderDetail(Request $request, $id)
    {
        try {
            $order = Order::with(['customer', 'items' => function ($q) {
                    $q->with(['category', 'brand']);
                }, 'transaction'])
                ->find($id);

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order tidak ditemukan',
                    'status' => 404,
                ], 404);
            }

            $data = $this->formatOrderDetail($order);

            return $this->sendResponse($data, 'Detail order berhasil diambil');
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . 'Terjadi kesalahan pada server',
                'status' => 500,
            ], 500);
        }
    }

    /**
     * Format order untuk list view (ringkas, dengan customer + total).
     */
    private function formatOrderSummary(Order $order): array
    {
        $totalItems = (int) $order->items->sum('quantity');
        $firstItem = $order->items->first();

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

    /**
     * Format order untuk detail view (lengkap, dengan semua items).
     */
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