<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuestionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'title' => $this->title,
            'description' => $this->description,
            'difficulty' => $this->difficulty,
            'explanation' => $this->explanation,
            'word_limit' => $this->word_limit,
            'marks_default' => $this->marks_default,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'options' => QuestionOptionResource::collection($this->whenLoaded('options')),
            'tags' => TagResource::collection($this->whenLoaded('tags')),
            'created_by' => $this->creator?->name,
            'updated_by' => $this->updater?->name,
            'pivot' => $this->whenPivotLoaded('test_section_question', function () {
                return [
                    'marks' => $this->pivot->marks,
                    'order' => $this->pivot->order,
                    'is_optional' => (bool) $this->pivot->is_optional,
                ];
            }),
        ];
    }
}
