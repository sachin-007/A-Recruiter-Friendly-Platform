<?php

namespace App\Http\Requests;

use App\Models\Test;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTestRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var \App\Models\Test|null $test */
        $test = $this->route('test');
        return $test ? ($this->user()?->can('update', $test) ?? false) : false;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'duration_minutes' => ['sometimes', 'integer', 'min:0', 'max:10080'],
            'instructions' => ['nullable', 'string'],
            'status' => ['nullable', Rule::in(['draft', 'published', 'archived'])],
        ];
    }
}
