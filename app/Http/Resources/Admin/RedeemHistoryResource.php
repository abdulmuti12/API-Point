<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RedeemHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'customer_id'     => $this->customer_id,
            'point_id'        => $this->point_id,
            'type'            => $this->type,
            'point_amount'    => (int) $this->point_amount,
            'point_balance'   => (int) $this->point_balance,
            'status'          => $this->status,
            'description'     => $this->description,
            'date'            => $this->created_at ? $this->created_at->toIso8601String() : null,
            'transaction_amount' => $this->transaction_amount !== null ? (int) $this->transaction_amount : null,
            'point'           => $this->point ? [
                'id'          => $this->point->id,
                'name'        => $this->point->name,
                'range_point' => (int) $this->point->range_point,
                'point'       => (int) $this->point->point,
            ] : null,
        ];
    }
}