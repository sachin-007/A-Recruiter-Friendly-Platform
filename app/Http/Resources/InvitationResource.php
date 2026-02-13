<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class InvitationResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'candidate_email' => $this->candidate_email,
            'candidate_name' => $this->candidate_name,
            'status' => $this->status,
            'token' => $this->token,
            'sent_at' => $this->sent_at,
            'opened_at' => $this->opened_at,
            'started_at' => $this->started_at,
            'completed_at' => $this->completed_at,
            'expires_at' => $this->expires_at,
            'test' => new TestResource($this->whenLoaded('test')),
            'creator' => new UserResource($this->whenLoaded('creator')),
            'attempt' => new AttemptResource($this->whenLoaded('attempt')),
        ];
    }
}