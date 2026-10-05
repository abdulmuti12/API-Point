<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\Gift;
use App\Models\RedeemPoint;
use App\Models\GiftClaim;

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
     *     "gifts": [
     *       {
     *         "id": "...",
     *         "name": "Holiday To Vrindapan",
     *         "total_point": 3000,
     *         "image": "http://.../storage/gifts/xxx.png",
     *         "description": "...",
     *         "claimable": false,
     *         "pending_claim": null | { id, status }
     *       },
     *       ...
     *     ]
     *   }
     * }
     */
    public function index(Request $request)
    {
        $customer = Auth::guard('customer')->user();

        if (!$customer) {
            return $this->sendError('Unauthorized', 'Silakan login terlebih dahulu', 401);
        }

        // Total point customer (basis point_active)
        $totalPoints = (int) RedeemPoint::where('customer_id', $customer->id)
            ->sum('total_point_active');

        // Ambil semua gift aktif
        $gifts = Gift::where('status', 'active')
            ->orderBy('total_point', 'asc')
            ->get();

        // Semua claim customer untuk menampilkan history (Open / Waiting / Closed)
        $allClaims = GiftClaim::where('customer_id', $customer->id)
            ->orderBy('id', 'desc')
            ->get()
            ->keyBy('gift_id');

        // Pending claims untuk pengecekan claimable
        $pendingClaims = $allClaims->filter(function ($c) {
            return in_array($c->status, ['waiting', 'approved']);
        });

        $formatted = $gifts->map(function ($gift) use ($totalPoints, $pendingClaims, $allClaims) {
            $pending = $pendingClaims->get($gift->id);
            $claim = $allClaims->get($gift->id);
            $claimStatus = null;
            if ($claim) {
                // Mapping dari DB ke label UI
                $map = [
                    'waiting' => 'Waiting',
                    'approved' => 'Open',
                    'rejected' => 'Closed',
                    'completed' => 'Closed',
                ];
                $claimStatus = $map[$claim->status] ?? ucfirst($claim->status);
            }
            return [
                'id'          => $gift->id,
                'name'        => $gift->name,
                'total_point' => (int) $gift->total_point,
                'image'       => $gift->image ? asset('storage/' . $gift->image) : null,
                'description' => $gift->description,
                'claimable'   => $totalPoints >= $gift->total_point && !$pending,
                'pending_claim' => $pending ? [
                    'id'     => $pending->id,
                    'status' => $pending->status,
                ] : null,
                'claim_status' => $claimStatus, // Waiting / Open / Closed
            ];
        })->values();

        $stats = [
            'total_points' => $totalPoints,
            'open_count'   => $allClaims->filter(fn($c) => in_array($c->status, ['waiting', 'approved']))->count(),
            'closed_count' => $allClaims->filter(fn($c) => in_array($c->status, ['rejected', 'completed']))->count(),
        ];

        return $this->sendResponse([
            'total_points' => $totalPoints,
            'gifts'        => $formatted,
            'stats'        => $stats,
        ], 'Data gifts berhasil diambil');
    }

    /**
     * POST /api/customers/gifts/claim
     * Body: { gift_id, notes? }
     * Customer request claim gift. Status awal = "waiting", belum potong point.
     * Point baru terpotong saat admin approve.
     */
    public function claim(Request $request)
    {
        $customer = Auth::guard('customer')->user();

        if (!$customer) {
            return $this->sendError('Unauthorized', 'Silakan login terlebih dahulu', 401);
        }

        $validator = Validator::make($request->all(), [
            'gift_id' => 'required|string|exists:gifts,id',
            'notes'   => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validasi gagal', $validator->errors());
        }

        $gift = Gift::where('status', 'active')->find($request->gift_id);
        if (!$gift) {
            return $this->sendError('Gift tidak tersedia', 'Gift tidak ditemukan atau tidak aktif');
        }

        // Cek apakah ada pending claim untuk gift ini
        $existing = GiftClaim::where('customer_id', $customer->id)
            ->where('gift_id', $gift->id)
            ->whereIn('status', ['waiting', 'approved'])
            ->first();

        if ($existing) {
            return $this->sendError(
                'Sudah ada request claim',
                "Anda sudah memiliki request claim untuk gift ini dengan status: {$existing->status}"
            );
        }

        // Hitung point_active customer saat ini
        $totalPoints = (int) RedeemPoint::where('customer_id', $customer->id)
            ->sum('total_point_active');

        if ($totalPoints < $gift->total_point) {
            return $this->sendError(
                'Point tidak cukup',
                "Anda membutuhkan {$gift->total_point} poin, saat ini hanya punya {$totalPoints}"
            );
        }

        DB::beginTransaction();
        try {
            $redeemPoint = RedeemPoint::where('customer_id', $customer->id)
                ->orderBy('id', 'desc')
                ->first();

            $claim = GiftClaim::create([
                'customer_id'              => $customer->id,
                'gift_id'                  => $gift->id,
                'redeem_point_id'          => $redeemPoint ? $redeemPoint->id : null,
                'required_point'           => $gift->total_point,
                'customer_point_at_request'=> $totalPoints,
                'status'                   => 'waiting',
                'notes'                    => $request->notes,
            ]);

            DB::commit();

            return $this->sendResponse([
                'id'             => $claim->id,
                'gift_id'        => $claim->gift_id,
                'gift_name'      => $gift->name,
                'required_point' => (int) $claim->required_point,
                'status'         => $claim->status,
                'notes'          => $claim->notes,
                'created_at'     => $claim->created_at ? $claim->created_at->toIso8601String() : null,
            ], 'Request claim berhasil dibuat, menunggu approval admin');
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
                'status'  => 500,
            ], 500);
        }
    }

    /**
     * GET /api/customers/gifts/claims
     * List semua claim customer (history: waiting, approved, rejected, completed)
     */
    public function claims(Request $request)
    {
        $customer = Auth::guard('customer')->user();

        if (!$customer) {
            return $this->sendError('Unauthorized', 'Silakan login terlebih dahulu', 401);
        }

        $query = GiftClaim::with('gift')
            ->where('customer_id', $customer->id)
            ->orderBy('id', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $claims = $query->get()->map(function ($claim) {
            return [
                'id'              => $claim->id,
                'gift_id'         => $claim->gift_id,
                'gift_name'       => $claim->gift ? $claim->gift->name : null,
                'gift_image'      => $claim->gift && $claim->gift->image ? asset('storage/' . $claim->gift->image) : null,
                'required_point'  => (int) $claim->required_point,
                'status'          => $claim->status,
                'notes'           => $claim->notes,
                'admin_note'      => $claim->admin_note,
                'created_at'      => $claim->created_at ? $claim->created_at->toIso8601String() : null,
                'approved_at'     => $claim->approved_at ? $claim->approved_at->toIso8601String() : null,
                'completed_at'    => $claim->completed_at ? $claim->completed_at->toIso8601String() : null,
            ];
        });

        return $this->sendResponse([
            'claims' => $claims,
        ], 'Data claim berhasil diambil');
    }
}