<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Question::class);
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['mcq', 'free_text', 'coding', 'sql'])],
            'title' => 'nullable|string|max:255',
            'description' => 'required|string',
            'difficulty' => 'required|in:easy,medium,hard',
            'explanation' => 'nullable|string',
            'word_limit' => 'nullable|integer|min:1',
            'marks_default' => 'required|integer|min:1',
            'status' => 'required|in:draft,active,archived',
            'tags' => 'array',
            'tags.*' => 'exists:tags,id',
            'options' => 'required_if:type,mcq|array',
            'options.*.option_text' => 'required|string',
            'options.*.is_correct' => 'required|boolean',
            'options.*.order' => 'nullable|integer',
        ];
    }
}