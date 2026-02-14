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
            'options' => 'nullable|array',
            'options.*.option_text' => [Rule::requiredIf(fn () => $this->input('type') === 'mcq'), 'string'],
            'options.*.is_correct' => [Rule::requiredIf(fn () => $this->input('type') === 'mcq'), 'boolean'],
            'options.*.order' => 'nullable|integer',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->input('type') !== 'mcq') {
                return;
            }

            $options = collect($this->input('options', []));

            if ($options->count() < 2) {
                $validator->errors()->add('options', 'MCQ questions must include at least two options.');
                return;
            }

            $correctCount = $options
                ->filter(fn ($option) => is_array($option) && (bool) ($option['is_correct'] ?? false))
                ->count();

            if ($correctCount < 1) {
                $validator->errors()->add('options', 'MCQ questions must include at least one correct option.');
            }
        });
    }
}
