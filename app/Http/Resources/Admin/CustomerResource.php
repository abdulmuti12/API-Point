<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'name'           => $this->name,
            'full_name'      => $this->full_name,
            'email'          => $this->email,
            'phone_number'   => $this->phone_number,
            'status'         => $this->status,
            'verify'         => (int) $this->verify,
            'address_text'   => $this->whenLoaded(
                'addressTexts',
                fn () => $this->addressTexts->map(fn ($at) => [
                    'id'   => $at->id,
                    'address' => $at->address,
                    'note' => $at->note,
                ])
            ),
        ];
    }
}