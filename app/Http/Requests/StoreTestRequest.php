<?php

namespace App\Http\Requests;

use App\Models\Test;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Test::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'duration_minutes' => ['required', 'integer', 'min:0', 'max:10080'],
            'instructions' => ['nullable', 'string'],
            'status' => ['nullable', Rule::in(['draft', 'published', 'archived'])],
        ];
    }
}
