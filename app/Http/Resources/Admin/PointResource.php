<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PointResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'name'         => $this->name,
            'status'       => $this->status,
            'price_point'  => (int) $this->price_point,
            'range_point'  => (int) $this->range_point,
            'point'        => (int) $this->point,
            'description'  => $this->description,
            'created_at'   => $this->created_at,
            'updated_at'   => $this->updated_at,
        ];
    }
}