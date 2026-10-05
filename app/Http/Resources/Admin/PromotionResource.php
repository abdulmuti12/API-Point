<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PromotionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'note' => $this->note,
            'type' => $this->type,
            'status' => $this->status,
            'brand' => @$this->brand->name,
            'file' => $this->file ? asset('storage/' . $this->file) : null,
            'file2' => $this->file2,
            'file3' => $this->file3,
            'file4' => $this->file4,
            'file5' => $this->file5,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];

    }
}
