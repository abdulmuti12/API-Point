<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RedeemPointResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'customer'            => $this->customer ? [
                'id'           => $this->customer->id,
                'name'         => $this->customer->name,
                'full_name'    => $this->customer->full_name,
                'email'        => $this->customer->email,
                'phone_number' => $this->customer->phone_number,
            ] : null,
            'point'               => $this->point ? [
                'id'          => $this->point->id,
                'name'        => $this->point->name,
                'status'      => $this->point->status,
                'price_point' => (int) $this->point->price_point,
                'range_point' => (int) $this->point->range_point,
                'point'       => (int) $this->point->point,
            ] : null,
            'total_transaction'   => (int) $this->total_transaction,
            'total_point'         => (int) ($this->total_point_active + $this->total_point_closed),
            'total_point_active'  => (int) $this->total_point_active,
            'total_point_closed'  => (int) $this->total_point_closed,
            'created_at'          => $this->created_at,
            'updated_at'          => $this->updated_at,
        ];
    }
}