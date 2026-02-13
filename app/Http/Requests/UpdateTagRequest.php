<?php

namespace App\Http\Requests;

use App\Models\Tag;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var \App\Models\Tag|null $tag */
        $tag = $this->route('tag');
        return $tag ? ($this->user()?->can('update', $tag) ?? false) : false;
    }

    public function rules(): array
    {
        /** @var \App\Models\Tag|null $tag */
        $tag = $this->route('tag');

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('tags', 'name')->ignore($tag?->id),
            ],
        ];
    }
}
