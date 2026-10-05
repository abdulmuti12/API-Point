<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\MemberLevel;
use App\Models\RedeemPoint;
use Illuminate\Support\Facades\Auth;

class CustomerMemberLevelController extends BaseController
{
    /**
     * GET /api/customers/member-level
     * Auth: Bearer token customer (jwt.customer)
     *
     * Response:
     * {
     *   "success": true,
     *   "data": {
     *     "total_points": 120,        // total point earned sepanjang masa
     *     "current_level": {
     *       "id": "...",
     *       "name": "Silver Card",
     *       "min_point": 5001,
     *       "max_point": 10000,
     *       "image_url": "http://.../storage/member_levels/xxx.png",
     *       "description": "..."
     *     } | null,
     *     "next_level": { ... } | null,
     *     "progress": {
     *       "current": 120,
     *       "min": 5001,
     *       "max": 10000,
     *       "percent": 2.4
     *     } | null
     *   }
     * }
     */
    public function show(Request $request)
    {
        $customer = Auth::guard('customer')->user();

        if (!$customer) {
            return $this->sendError('Unauthorized', 'Silakan login terlebih dahulu', 401);
        }

        // Total point sepanjang masa (basis level customer)
        $totalPoints = (int) RedeemPoint::where('customer_id', $customer->id)
            ->sum('total_point_earned');

        // Ambil semua member level yang aktif, diurutkan ascending by min_point
        $levels = MemberLevel::where('status', 'active')
            ->orderBy('min_point', 'asc')
            ->get();

        // Tentukan current level:
        // - Level dengan min_point <= total_point <= max_point
        // - Jika max_point = 0 atau null, dianggap level tertinggi (unlimited)
        $currentLevel = null;
        foreach ($levels as $level) {
            $min = (int) $level->min_point;
            $max = (int) $level->max_point;

            if ($totalPoints >= $min && ($max === 0 || $totalPoints <= $max)) {
                $currentLevel = $level;
                break;
            }
        }

        // Tentukan next level (level dengan min_point lebih besar dari current)
        $nextLevel = null;
        if ($currentLevel) {
            $nextLevel = $levels->first(function ($l) use ($currentLevel) {
                return (int) $l->min_point > (int) $currentLevel->min_point;
            });
        } else {
            // Belum punya level: next level = level dengan min_point terkecil yang masih > totalPoints
            $nextLevel = $levels->first(function ($l) use ($totalPoints) {
                return (int) $l->min_point > $totalPoints;
            });
        }

        // Hitung progress ke next level
        $progress = null;
        if ($nextLevel) {
            $baseMin = $currentLevel ? (int) $currentLevel->min_point : 0;
            $target = (int) $nextLevel->min_point;
            $range = max(1, $target - $baseMin);
            $earned = max(0, $totalPoints - $baseMin);
            $percent = round(($earned / $range) * 100, 2);

            $progress = [
                'current' => $totalPoints,
                'min'     => $baseMin,
                'target'  => $target,
                'percent' => min(100, $percent),
            ];
        }

        $formatLevel = function ($level) {
            if (!$level) return null;
            return [
                'id'          => $level->id,
                'name'        => $level->name,
                'min_point'   => (int) $level->min_point,
                'max_point'   => (int) $level->max_point,
                'image_url'   => $level->file ? asset('storage/' . $level->file) : null,
                'description' => $level->description,
            ];
        };

        // Serialize semua level untuk ditampilkan sebagai grid cards di frontend
        $allLevels = $levels->map($formatLevel)->values();

        return $this->sendResponse([
            'total_points'  => $totalPoints,
            'current_level' => $formatLevel($currentLevel),
            'next_level'    => $formatLevel($nextLevel),
            'progress'      => $progress,
            'all_levels'    => $allLevels,
        ], 'Data member level berhasil diambil');
    }
}