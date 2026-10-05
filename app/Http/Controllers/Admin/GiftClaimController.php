<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\GiftClaim;
use App\Models\Gift;
use App\Models\RedeemPoint;
use App\Models\RedeemHistory;
use App\Http\Resources\Admin\GiftClaimResource;

class GiftClaimController extends BaseController
{
    private $model;
    private $route = 'gift-claims';
    public $title = "Gift Claims";

    public function __construct(GiftClaim $model)
    {
        $this->model = $model;
    }

    /**
     * GET /api/admins/gift-claims
     * List semua request claim gift customer.
     * Support filter: ?status=waiting|approved|rejected|completed&customer_name=xxx&gift_id=uuid
     */
    public function index(Request $request)
    {
        $dataRequest = $request->all() ?? [];
        $page = $request->query('page') ?? 1;

        $query = $this->model->with(['customer', 'gift', 'approver']);

        if (!empty($dataRequest['status'])) {
            $query->where('status', $dataRequest['status']);
        }

        if (!empty($dataRequest['gift_id'])) {
            $query->where('gift_id', $dataRequest['gift_id']);
        }

        if (!empty($dataRequest['customer_name'])) {
            $keyword = $dataRequest['customer_name'];
            $query->whereHas('customer', function ($q) use ($keyword) {
                $q->where('name', 'like', '%' . $keyword . '%')
                  ->orWhere('full_name', 'like', '%' . $keyword . '%')
                  ->orWhere('email', 'like', '%' . $keyword . '%');
            });
        }

        $collection = $query->orderBy('id', 'desc')->paginate(10, ['*'], 'page', $page);
        $rawData = GiftClaimResource::collection($collection);

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

        return $this->sendResponse($datas, 'Data gift claims berhasil diambil');
    }

    /**
     * GET /api/admins/gift-claims/{id}
     * Detail satu claim + info customer + gift
     */
    public function show($id)
    {
        $claim = GiftClaim::with(['customer', 'gift', 'approver'])->find($id);
        if (!$claim) {
            return $this->sendError('Claim tidak ditemukan', 'Data tidak ditemukan');
        }

        return $this->sendResponse(
            new GiftClaimResource($claim),
            'Detail claim berhasil diambil'
        );
    }

    /**
     * POST /api/admins/gift-claims/{id}/approve
     * Body: { admin_note? }
     * Approve request claim:
     *   - Set status = approved
     *   - Potong point customer: kurangi total_point_active, tambah ke total_point_closed
     *   - Catat RedeemHistory dengan type=claim & status=closed
     */
    public function approve(Request $request, $id)
    {
        $admin = Auth::guard('admins')->user();

        $validator = Validator::make($request->all(), [
            'admin_note' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validasi gagal', $validator->errors());
        }

        $claim = GiftClaim::with('customer')->find($id);
        if (!$claim) {
            return $this->sendError('Claim tidak ditemukan', 'Data tidak ditemukan');
        }

        if ($claim->status !== 'waiting') {
            return $this->sendError(
                'Status tidak valid',
                "Claim dengan status {$claim->status} tidak bisa di-approve (harus waiting)"
            );
        }

        // Cari redeem point customer (yang paling baru / paling aktif)
        $redeemPoint = RedeemPoint::where('customer_id', $claim->customer_id)
            ->orderBy('id', 'desc')
            ->first();

        if (!$redeemPoint) {
            return $this->sendError(
                'Point customer tidak ditemukan',
                'Customer belum memiliki redeem point'
            );
        }

        // Double-check: customer harus punya cukup point aktif
        if ($redeemPoint->total_point_active < $claim->required_point) {
            return $this->sendError(
                'Point aktif tidak cukup',
                "Point aktif customer {$redeemPoint->total_point_active}, tidak bisa approve claim {$claim->required_point}"
            );
        }

        DB::beginTransaction();
        try {
            // Potong point: active -= required, closed += required
            $redeemPoint->total_point_active  = $redeemPoint->total_point_active - $claim->required_point;
            $redeemPoint->total_point_closed  = $redeemPoint->total_point_closed + $claim->required_point;
            $redeemPoint->total_point_claimed = $redeemPoint->total_point_claimed + $claim->required_point;
            $redeemPoint->save();

            // Catat history claim (point_balance = sisa active setelah potong)
            $balance = (int) $redeemPoint->total_point_active;

            RedeemHistory::create([
                'redeem_point_id' => $redeemPoint->id,
                'customer_id'     => $claim->customer_id,
                'point_id'        => $redeemPoint->point_id,
                'type'            => 'claim',
                'point_amount'    => $claim->required_point,
                'point_balance'   => $balance,
                'status'          => 'closed',
                'description'     => "Claim gift \"{$claim->gift->name}\" ({$claim->required_point} poin) - approved" . ($request->admin_note ? " | Note: {$request->admin_note}" : ''),
            ]);

            // Update claim
            $claim->status       = 'approved';
            $claim->approved_at  = now();
            $claim->approved_by  = $admin ? $admin->id : null;
            $claim->admin_note   = $request->admin_note;
            $claim->save();

            DB::commit();

            return $this->sendResponse(
                new GiftClaimResource($claim->load(['customer', 'gift', 'approver'])),
                "Claim approved, {$claim->required_point} point telah dipotong dari customer"
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
     * POST /api/admins/gift-claims/{id}/reject
     * Body: { admin_note }
     * Reject request claim:
     *   - Set status = rejected
     *   - Point customer TIDAK dipotong
     *   - admin_note WAJIB diisi (alasan penolakan)
     */
    public function reject(Request $request, $id)
    {
        $admin = Auth::guard('admins')->user();

        $validator = Validator::make($request->all(), [
            'admin_note' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validasi gagal', $validator->errors());
        }

        $claim = GiftClaim::find($id);
        if (!$claim) {
            return $this->sendError('Claim tidak ditemukan', 'Data tidak ditemukan');
        }

        if ($claim->status !== 'waiting') {
            return $this->sendError(
                'Status tidak valid',
                "Claim dengan status {$claim->status} tidak bisa di-reject (harus waiting)"
            );
        }

        $claim->status      = 'rejected';
        $claim->approved_at = now();
        $claim->approved_by = $admin ? $admin->id : null;
        $claim->admin_note  = $request->admin_note;
        $claim->save();

        return $this->sendResponse(
            new GiftClaimResource($claim->load(['customer', 'gift', 'approver'])),
            'Claim ditolak, point customer tetap aman'
        );
    }

    /**
     * POST /api/admins/gift-claims/{id}/complete
     * Tandai hadiah sudah diserahkan ke customer (step manual setelah approve).
     * Set status = completed, completed_at = now()
     */
    public function complete(Request $request, $id)
    {
        $claim = GiftClaim::find($id);
        if (!$claim) {
            return $this->sendError('Claim tidak ditemukan', 'Data tidak ditemukan');
        }

        if ($claim->status !== 'approved') {
            return $this->sendError(
                'Status tidak valid',
                "Hanya claim dengan status 'approved' yang bisa ditandai selesai (saat ini: {$claim->status})"
            );
        }

        $claim->status       = 'completed';
        $claim->completed_at = now();
        $claim->save();

        return $this->sendResponse(
            new GiftClaimResource($claim->load(['customer', 'gift', 'approver'])),
            'Claim ditandai selesai (hadiah sudah diterima customer)'
        );
    }
}