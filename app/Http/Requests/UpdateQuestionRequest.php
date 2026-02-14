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
            'options.*.option_text' => [
                Rule::requiredIf(fn () => $this->resolvedType() === 'mcq' && $this->has('options')),
                'string',
            ],
            'options.*.is_correct' => [
                Rule::requiredIf(fn () => $this->resolvedType() === 'mcq' && $this->has('options')),
                'boolean',
            ],
            'options.*.order' => ['nullable', 'integer'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->resolvedType() !== 'mcq') {
                return;
            }

            if ($this->input('type') === 'mcq' && ! $this->has('options')) {
                $validator->errors()->add('options', 'MCQ questions must include options.');
                return;
            }

            if (! $this->has('options')) {
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

    private function resolvedType(): ?string
    {
        return $this->input('type') ?? $this->route('question')?->type;
    }
}
