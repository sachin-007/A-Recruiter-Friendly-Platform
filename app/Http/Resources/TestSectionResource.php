<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TestSectionResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'order' => $this->order,
            'questions' => QuestionResource::collection($this->whenLoaded('questions'))->map(function ($q) {
                // Add pivot data
                $q->additional(['pivot' => [
                    'marks' => $q->pivot->marks,
                    'order' => $q->pivot->order,
                    'is_optional' => $q->pivot->is_optional,
                ]]);
                return $q;
            }),
        ];
    }
}