<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Customer;
use App\Models\Category;
use App\Models\Brand;
use App\Http\Resources\Admin\OrderResource;
use App\Services\Admin\OrderService;
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
    public function store(Request $request, OrderService $service)
    {
        $result = $service->store($request->all());

        if (isset($result['error'])) {
            return $this->sendError($result['error'], $result['errors']);
        }

        return $this->sendResponse($result['order'], 'Transaksi berhasil ditambahkan');
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
    public function getOrders(Request $request, OrderService $service)
    {
        try {
            $dataRequest = $request->all() ?? [];
            $page = $request->query('page') ?? 1;
            $perPage = $request->query('per_page') ?? 10;
            $raw = (bool) $request->query('raw') ?? false;

            $datas = $service->getOrders($dataRequest, (int) $page, (int) $perPage);

            if ($raw) {
                $datas = $datas['data'];
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
    public function getOrderDetail(Request $request, $id, OrderService $service)
    {
        try {
            $data = $service->getOrderDetail((int) $id);

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
                'message' => 'Terjadi kesalahan: ' . 'Terjadi kesalahan pada server',
                'status' => 500,
            ], 500);
        }
    }
}