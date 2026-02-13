<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AttemptResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'candidate_email' => $this->candidate_email,
            'candidate_name' => $this->candidate_name,
            'started_at' => $this->started_at,
            'completed_at' => $this->completed_at,
            'time_remaining' => $this->time_remaining,
            'status' => $this->status,
            'score_total' => $this->score_total,
            'score_percent' => $this->score_percent,
            'test' => new TestResource($this->whenLoaded('test')),
            'answers' => AttemptAnswerResource::collection($this->whenLoaded('answers')),
            'invitation' => new InvitationResource($this->whenLoaded('invitation')),
        ];
    }
}