<?php

namespace App\Http\Requests;

use App\Models\Question;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var \App\Models\Question|null $question */
        $question = $this->route('question');
        return $question ? ($this->user()?->can('update', $question) ?? false) : false;
    }

    public function rules(): array
    {
        return [
            'type' => ['sometimes', Rule::in(['mcq', 'free_text', 'coding', 'sql'])],
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['sometimes', 'string'],
            'difficulty' => ['sometimes', Rule::in(['easy', 'medium', 'hard'])],
            'explanation' => ['nullable', 'string'],
            'word_limit' => ['nullable', 'integer', 'min:1'],
            'marks_default' => ['sometimes', 'integer', 'min:1'],
            'status' => ['sometimes', Rule::in(['draft', 'active', 'archived'])],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['exists:tags,id'],
            'options' => ['sometimes', 'array'],
            'options.*.option_text' => ['required_with:options', 'string'],
            'options.*.is_correct' => ['required_with:options', 'boolean'],
            'options.*.order' => ['nullable', 'integer'],
        ];
    }
}
