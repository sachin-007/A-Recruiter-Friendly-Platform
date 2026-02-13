<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AttemptAnswerResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'question_id' => $this->question_id,
            'answer_json' => $this->answer_json,
            'is_correct' => $this->is_correct,
            'marks_awarded' => $this->marks_awarded,
            'reviewed_at' => $this->reviewed_at,
            'reviewer' => new UserResource($this->whenLoaded('reviewer')),
            'question' => new QuestionResource($this->whenLoaded('question')),
        ];
    }
}