<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Point;
use App\Models\RedeemPoint;
use App\Models\RedeemHistory;
use App\Http\Resources\Admin\RedeemPointResource;
use App\Http\Resources\Admin\RedeemHistoryResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RedeemPointController extends BaseController
{
    private $model;
    private $route = 'redeem-points';
    public $title = "Redeem Points";

    public function __construct(RedeemPoint $model)
    {
        $this->model = $model;
    }

    /**
     * GET /api/admins/redeem-point
     * List semua customer yang punya point (di-group by customer).
     * Menampilkan: nama customer, total point, total_point_active, total_point_closed.
     * Support filter: ?customer_name=xxx
     */
    public function index(Request $request)
    {
        $dataRequest = $request->all() ?? [];
        $page = $request->query('page') ?? 1;
        $raw = (bool) $request->query('raw') ?? false;

        $query = RedeemPoint::with(['customer', 'point']);

        if (!empty($dataRequest['customer_name'])) {
            $keyword = $dataRequest['customer_name'];
            $query->whereHas('customer', function ($q) use ($keyword) {
                $q->where('name', 'like', '%' . $keyword . '%')
                  ->orWhere('full_name', 'like', '%' . $keyword . '%')
                  ->orWhere('email', 'like', '%' . $keyword . '%');
            });
        }

        $collection = $query->orderBy('id', 'desc')->paginate(10, ['*'], 'page', $page);
        $rawData = RedeemPointResource::collection($collection);

        if ($raw) {
            $datas = $rawData;
        } else {
            $responses = $rawData->response()->getData(true);
            $links = $responses['links'];
            $meta = $responses['meta'];
            unset($responses['links']);
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
     * GET /api/admins/redeem-point/{customerId}
     * Detail per customer:
     *   - info customer
     *   - summary: total point, total_point_active, total_point_closed
     *   - history: earn + claim (point aktif + point non-aktif)
     */
    public function show($customerId)
    {
        $customer = Customer::find($customerId);
        if (!$customer) {
            return $this->sendError('Customer tidak ditemukan', 'Data tidak ditemukan');
        }

        $redeemPoints = RedeemPoint::with(['point', 'histories.point'])
            ->where('customer_id', $customerId)
            ->orderBy('id', 'desc')
            ->get();

        $summary = [
            'customer'           => [
                'id'           => $customer->id,
                'name'         => $customer->name,
                'full_name'    => $customer->full_name,
                'email'        => $customer->email,
                'phone_number' => $customer->phone_number,
            ],
            'total_point'        => (int) $redeemPoints->sum(fn ($rp) => $rp->total_point_active + $rp->total_point_closed),
            'total_point_active' => (int) $redeemPoints->sum('total_point_active'),
            'total_point_closed' => (int) $redeemPoints->sum('total_point_closed'),
            'total_earned'       => (int) $redeemPoints->sum('total_point_earned'),
            'total_claimed'      => (int) $redeemPoints->sum('total_point_claimed'),
        ];

        $histories = RedeemHistory::with('point')
            ->where('customer_id', $customerId)
            ->orderBy('id', 'desc')
            ->get();

        $grouped = [
            'active' => RedeemHistoryResource::collection(
                $histories->where('status', 'active')->values()
            ),
            'closed' => RedeemHistoryResource::collection(
                $histories->where('status', 'closed')->values()
            ),
        ];

        $data = [
            'summary'  => $summary,
            'redeems'  => RedeemPointResource::collection($redeemPoints),
            'history'  => [
                'all'    => RedeemHistoryResource::collection($histories),
                'active' => $grouped['active'],
                'closed' => $grouped['closed'],
            ],
        ];

        return $this->sendResponse($data, 'Detail customer berhasil diambil');
    }

    /**
     * POST /api/admins/redeem-point/calculate/{customerId}
     * Hitung point customer berdasarkan total transaksi sukses di orders.
     * Logika:
     *   - Ambil total_transaksi sukses dari orders.customer_id
     *   - Cari rule point yang status=active
     *   - point = floor(total_transaction / range_point) * point_value
     *   - Contoh: 1.000.000 / 100.000 = 10 point
     *   - Contoh: 1.075.000 / 100.000 = 10 point (floor, 75.000 terbuang)
     *   - Hanya menambahkan jika ada point baru (selisih > 0)
     */
    public function calculate($customerId)
    {
        $customer = Customer::find($customerId);
        if (!$customer) {
            return $this->sendError('Customer tidak ditemukan', 'Data tidak ditemukan');
        }

        $pointRule = Point::where('status', 'active')->first();
        if (!$pointRule) {
            return $this->sendError('Rule point aktif tidak ditemukan', 'Tidak ada point rule dengan status active');
        }

        $totalTransaction = (int) Order::where('customer_id', $customerId)
            ->whereIn('status', ['delivered', 'success'])
            ->sum('grand_total');

        if ($totalTransaction <= 0) {
            return $this->sendError('Tidak ada transaksi', 'Customer belum memiliki transaksi sukses');
        }

        $totalPointEarned = intdiv($totalTransaction, (int) $pointRule->range_point) * (int) $pointRule->point;

        if ($totalPointEarned <= 0) {
            return $this->sendError(
                'Belum cukup transaksi',
                "Total transaksi {$totalTransaction} belum memenuhi range_point {$pointRule->range_point} untuk mendapat point"
            );
        }

        DB::beginTransaction();
        try {
            $redeemPoint = RedeemPoint::where('customer_id', $customerId)
                ->where('point_id', $pointRule->id)
                ->first();

            $previousEarned = $redeemPoint ? (int) $redeemPoint->total_point_earned : 0;
            $newPoint = $totalPointEarned - $previousEarned;

            if ($newPoint <= 0) {
                DB::rollBack();
                return $this->sendResponse(
                    $redeemPoint,
                    'Tidak ada point baru (point sudah pernah dihitung sebelumnya)'
                );
            }

            if (!$redeemPoint) {
                $redeemPoint = RedeemPoint::create([
                    'customer_id'         => $customerId,
                    'point_id'            => $pointRule->id,
                    'total_transaction'   => $totalTransaction,
                    'total_point_earned'  => $totalPointEarned,
                    'total_point_claimed' => 0,
                    'total_point_active'  => $newPoint,
                    'total_point_closed'  => 0,
                ]);
            } else {
                $redeemPoint->total_transaction  = $totalTransaction;
                $redeemPoint->total_point_earned = $totalPointEarned;
                $redeemPoint->total_point_active = $redeemPoint->total_point_active + $newPoint;
                $redeemPoint->save();
            }

            $balance = (int) ($redeemPoint->total_point_active + $redeemPoint->total_point_closed);

            RedeemHistory::create([
                'redeem_point_id' => $redeemPoint->id,
                'customer_id'     => $customerId,
                'point_id'        => $pointRule->id,
                'type'            => 'earn',
                'point_amount'    => $newPoint,
                'point_balance'   => $balance,
                'status'          => 'active',
                'description'     => "Earn {$newPoint} point dari total transaksi {$totalTransaction} (rule: {$pointRule->name})",
            ]);

            DB::commit();

            return $this->sendResponse(
                new RedeemPointResource($redeemPoint->load(['customer', 'point'])),
                "Berhasil menambahkan {$newPoint} point untuk customer {$customer->name}"
            );
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . 'Terjadi kesalahan pada server',
                'status'  => 500,
            ], 500);
        }
    }

    /**
     * POST /api/admins/redeem-point/claim
     * Body: { customer_id, point_amount, description? }
     * Claim point: kurangi total_point_active, tambah ke total_point_closed
     * Catat history dengan type=claim & status=closed.
     */
    public function claim(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_id'  => 'required|string|exists:customers,id',
            'point_amount' => 'required|integer|min:1',
            'description'  => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validasi gagal', $validator->errors());
        }

        $customerId = $request->customer_id;
        $claimAmount = (int) $request->point_amount;

        $customer = Customer::find($customerId);
        if (!$customer) {
            return $this->sendError('Customer tidak ditemukan', 'Data tidak ditemukan');
        }

        $redeemPoint = RedeemPoint::where('customer_id', $customerId)
            ->orderBy('id', 'desc')
            ->first();

        if (!$redeemPoint) {
            return $this->sendError('Point tidak ditemukan', 'Customer belum memiliki point');
        }

        if ($redeemPoint->total_point_active < $claimAmount) {
            return $this->sendError(
                'Point aktif tidak cukup',
                "Point aktif {$redeemPoint->total_point_active}, tidak bisa claim {$claimAmount}"
            );
        }

        DB::beginTransaction();
        try {
            $redeemPoint->total_point_active  = $redeemPoint->total_point_active - $claimAmount;
            $redeemPoint->total_point_closed  = $redeemPoint->total_point_closed + $claimAmount;
            $redeemPoint->total_point_claimed = $redeemPoint->total_point_claimed + $claimAmount;
            $redeemPoint->save();

            $balance = (int) ($redeemPoint->total_point_active + $redeemPoint->total_point_closed);

            $history = RedeemHistory::create([
                'redeem_point_id' => $redeemPoint->id,
                'customer_id'     => $customerId,
                'point_id'        => $redeemPoint->point_id,
                'type'            => 'claim',
                'point_amount'    => $claimAmount,
                'point_balance'   => $balance,
                'status'          => 'closed',
                'description'     => $request->description ?? "Claim {$claimAmount} point oleh customer {$customer->name}",
            ]);

            DB::commit();

            return $this->sendResponse([
                'redeem'  => new RedeemPointResource($redeemPoint->load(['customer', 'point'])),
                'history' => new RedeemHistoryResource($history),
            ], "Claim {$claimAmount} point berhasil");
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . 'Terjadi kesalahan pada server',
                'status'  => 500,
            ], 500);
        }
    }

    /**
     * GET /api/admins/redeem-point/history/{customerId}
     * List history point customer (penambahan + claim, aktif + non-aktif).
     * Support filter: ?type=earn|claim&status=active|closed
     */
    public function history(Request $request, $customerId)
    {
        $customer = Customer::find($customerId);
        if (!$customer) {
            return $this->sendError('Customer tidak ditemukan', 'Data tidak ditemukan');
        }

        $query = RedeemHistory::with('point')
            ->where('customer_id', $customerId);

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $page = $request->query('page') ?? 1;
        $collection = $query->orderBy('id', 'desc')->paginate(10, ['*'], 'page', $page);

        $rawData = RedeemHistoryResource::collection($collection);

        $responses = $rawData->response()->getData(true);
        $links = $responses['links'];
        $meta = $responses['meta'];
        unset($responses['links']);
        unset($responses['meta']);

        $datas = [
            'data'  => $responses,
            'meta'  => $meta,
            'links' => $links,
        ];

        return $this->sendResponse($datas, 'History berhasil diambil');
    }
}