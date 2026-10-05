<?php
namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

class BannerResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'decription' => $this->decription,
            'note' => $this->note,
            'type' => $this->type,
            'file' => $this->file,
            'file2' => $this->file2,
            'file3' => $this->file3,
            'file4' => $this->file4,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

