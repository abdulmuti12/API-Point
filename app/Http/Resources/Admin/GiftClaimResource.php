<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GiftClaimResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                        => $this->id,
            'customer_id'               => $this->customer_id,
            'gift_id'                   => $this->gift_id,
            'redeem_point_id'           => $this->redeem_point_id,
            'approved_by'               => $this->approved_by,
            'required_point'            => (int) $this->required_point,
            'customer_point_at_request' => (int) $this->customer_point_at_request,
            'status'                    => $this->status,
            'notes'                     => $this->notes,
            'admin_note'                => $this->admin_note,
            'approved_at'               => $this->approved_at ? $this->approved_at->toIso8601String() : null,
            'completed_at'              => $this->completed_at ? $this->completed_at->toIso8601String() : null,
            'created_at'                => $this->created_at ? $this->created_at->toIso8601String() : null,
            'updated_at'                => $this->updated_at ? $this->updated_at->toIso8601String() : null,
            'customer' => $this->whenLoaded('customer', function () {
                return [
                    'id'           => $this->customer->id,
                    'name'         => $this->customer->name,
                    'full_name'    => $this->customer->full_name,
                    'email'        => $this->customer->email,
                    'phone_number' => $this->customer->phone_number,
                ];
            }),
            'gift' => $this->whenLoaded('gift', function () {
                return [
                    'id'          => $this->gift->id,
                    'name'        => $this->gift->name,
                    'total_point' => (int) $this->gift->total_point,
                    'image'       => $this->gift->image,
                    'description' => $this->gift->description,
                ];
            }),
            'approver' => $this->whenLoaded('approver', function () {
                return $this->approver ? [
                    'id'    => $this->approver->id,
                    'name'  => $this->approver->name,
                    'email' => $this->approver->email,
                ] : null;
            }),
        ];
    }
}