<?php

namespace App\Services\Customer;

use App\Models\Customer;
use App\Models\Gift;
use App\Models\GiftClaim;
use App\Models\RedeemPoint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class GiftService
{
    /**
     * Data gifts + claimable status untuk customer.
     * Return array dengan shape yang sama persis seperti GiftCustomerController@index.
     */
    public function indexForCustomer(Customer $customer): array
    {
        $totalPoints = (int) RedeemPoint::where('customer_id', $customer->id)
            ->sum('total_point_active');

        $gifts = Gift::where('status', 'active')
            ->orderBy('total_point', 'asc')
            ->get();

        $allClaims = GiftClaim::where('customer_id', $customer->id)
            ->orderBy('id', 'desc')
            ->get()
            ->keyBy('gift_id');

        $pendingClaims = $allClaims->filter(function ($c) {
            return in_array($c->status, ['waiting', 'approved']);
        });

        $formatted = $gifts->map(function ($gift) use ($totalPoints, $pendingClaims, $allClaims) {
            $pending = $pendingClaims->get($gift->id);
            $claim = $allClaims->get($gift->id);
            $claimStatus = null;
            if ($claim) {
                $map = [
                    'waiting'  => 'Waiting',
                    'approved' => 'Open',
                    'rejected' => 'Closed',
                    'completed'=> 'Closed',
                ];
                $claimStatus = $map[$claim->status] ?? ucfirst($claim->status);
            }
            return [
                'id'           => $gift->id,
                'name'         => $gift->name,
                'total_point'  => (int) $gift->total_point,
                'image'        => $gift->image ? asset('storage/' . $gift->image) : null,
                'description'  => $gift->description,
                'claimable'    => $totalPoints >= $gift->total_point && !$pending,
                'pending_claim'=> $pending ? [
                    'id'     => $pending->id,
                    'status' => $pending->status,
                ] : null,
                'claim_status' => $claimStatus,
            ];
        })->values();

        $stats = [
            'total_points' => $totalPoints,
            'open_count'   => $allClaims->filter(fn($c) => in_array($c->status, ['waiting', 'approved']))->count(),
            'closed_count' => $allClaims->filter(fn($c) => in_array($c->status, ['rejected', 'completed']))->count(),
        ];

        return [
            'total_points' => $totalPoints,
            'gifts'        => $formatted,
            'stats'        => $stats,
        ];
    }

    /**
     * Buat request claim gift (status awal "waiting").
     * Return:
     *  - array berisi data claim bila berhasil
     *  - array ['error' => ..., 'message' => ...] bila ditolak
     */
    public function claimForCustomer(Customer $customer, array $request): array
    {
        $validator = Validator::make($request, [
            'gift_id' => 'required|string|exists:gifts,id',
            'notes'   => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return ['error' => 'Validasi gagal', 'message' => $validator->errors()];
        }

        $gift = Gift::where('status', 'active')->find($request['gift_id']);
        if (!$gift) {
            return ['error' => 'Gift tidak tersedia', 'message' => 'Gift tidak ditemukan atau tidak aktif'];
        }

        $existing = GiftClaim::where('customer_id', $customer->id)
            ->where('gift_id', $gift->id)
            ->whereIn('status', ['waiting', 'approved'])
            ->first();

        if ($existing) {
            return [
                'error'   => 'Sudah ada request claim',
                'message' => "Anda sudah memiliki request claim untuk gift ini dengan status: {$existing->status}",
            ];
        }

        $totalPoints = (int) RedeemPoint::where('customer_id', $customer->id)
            ->sum('total_point_active');

        if ($totalPoints < $gift->total_point) {
            return [
                'error'   => 'Point tidak cukup',
                'message' => "Anda membutuhkan {$gift->total_point} poin, saat ini hanya punya {$totalPoints}",
            ];
        }

        DB::beginTransaction();
        try {
            $redeemPoint = RedeemPoint::where('customer_id', $customer->id)
                ->orderBy('id', 'desc')
                ->first();

            $claim = GiftClaim::create([
                'customer_id'               => $customer->id,
                'gift_id'                   => $gift->id,
                'redeem_point_id'           => $redeemPoint ? $redeemPoint->id : null,
                'required_point'            => $gift->total_point,
                'customer_point_at_request' => $totalPoints,
                'status'                    => 'waiting',
                'notes'                     => $request['notes'] ?? null,
            ]);

            DB::commit();

            return [
                'data' => [
                    'id'             => $claim->id,
                    'gift_id'        => $claim->gift_id,
                    'gift_name'      => $gift->name,
                    'required_point' => (int) $claim->required_point,
                    'status'         => $claim->status,
                    'notes'          => $claim->notes,
                    'created_at'     => $claim->created_at ? $claim->created_at->toIso8601String() : null,
                ],
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            return ['error' => 'Server Error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Riwayat claim customer (semua status).
     * Return: array berisi 'claims' (list claim history customer).
     */
    public function claimsForCustomer(Customer $customer, ?string $statusFilter = null): array
    {
        $query = GiftClaim::with('gift')
            ->where('customer_id', $customer->id)
            ->orderBy('id', 'desc');

        if ($statusFilter) {
            $query->where('status', $statusFilter);
        }

        $claims = $query->get()->map(function ($claim) {
            return [
                'id'            => $claim->id,
                'gift_id'       => $claim->gift_id,
                'gift_name'     => $claim->gift ? $claim->gift->name : null,
                'gift_image'    => $claim->gift && $claim->gift->image ? asset('storage/' . $claim->gift->image) : null,
                'required_point'=> (int) $claim->required_point,
                'status'        => $claim->status,
                'notes'         => $claim->notes,
                'admin_note'    => $claim->admin_note,
                'created_at'    => $claim->created_at ? $claim->created_at->toIso8601String() : null,
                'approved_at'   => $claim->approved_at ? $claim->approved_at->toIso8601String() : null,
                'completed_at'  => $claim->completed_at ? $claim->completed_at->toIso8601String() : null,
            ];
        });

        return ['claims' => $claims];
    }
}
