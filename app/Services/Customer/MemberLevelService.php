<?php

namespace App\Services\Customer;

use App\Models\Customer;
use App\Models\MemberLevel;
use App\Models\RedeemPoint;

class MemberLevelService
{
    /**
     * Kalkulasi member level & progress customer.
     * Return array dengan shape yang sama persis seperti CustomerMemberLevelController@show.
     */
    public function calculateForCustomer(Customer $customer): array
    {
        $totalPoints = (int) RedeemPoint::where('customer_id', $customer->id)
            ->sum('total_point_earned');

        $levels = MemberLevel::where('status', 'active')
            ->orderBy('min_point', 'asc')
            ->get();

        $currentLevel = null;
        foreach ($levels as $level) {
            $min = (int) $level->min_point;
            $max = (int) $level->max_point;
            if ($totalPoints >= $min && ($max === 0 || $totalPoints <= $max)) {
                $currentLevel = $level;
                break;
            }
        }

        $nextLevel = null;
        if ($currentLevel) {
            $nextLevel = $levels->first(function ($l) use ($currentLevel) {
                return (int) $l->min_point > (int) $currentLevel->min_point;
            });
        } else {
            $nextLevel = $levels->first(function ($l) use ($totalPoints) {
                return (int) $l->min_point > $totalPoints;
            });
        }

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

        $allLevels = $levels->map($formatLevel)->values();

        return [
            'total_points'  => $totalPoints,
            'current_level' => $formatLevel($currentLevel),
            'next_level'    => $formatLevel($nextLevel),
            'progress'      => $progress,
            'all_levels'    => $allLevels,
        ];
    }
}
