<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class OrganizationResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'logo' => $this->logo ? Storage::disk('public')->url($this->logo) : null,
            'settings' => $this->settings,
            'users_count' => $this->whenCounted('users'),
            'questions_count' => $this->whenCounted('questions'),
            'tests_count' => $this->whenCounted('tests'),
            'invitations_count' => $this->whenCounted('invitations'),
            'completed_attempts_count' => $this->when(
                isset($this->completed_attempts_count),
                (int) $this->completed_attempts_count
            ),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
