<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ImportResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'file_name' => $this->file_name,
            'status' => $this->status,
            'total_rows' => $this->total_rows,
            'processed_rows' => $this->processed_rows,
            'error_log' => $this->error_log,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'importer' => new UserResource($this->whenLoaded('importer')),
        ];
    }
}