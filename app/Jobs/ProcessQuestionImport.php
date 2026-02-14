<?php

namespace App\Jobs;

use App\Models\Import;
use App\Models\Question;
use App\Models\Tag;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProcessQuestionImport implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public Import $import)
    {
    }

    public function handle(): void
    {
        $this->import->refresh();
        $this->import->update([
            'status' => 'processing',
            'total_rows' => 0,
            'processed_rows' => 0,
            'error_log' => null,
        ]);

        $errors = [];
        $totalRows = 0;
        $processedRows = 0;

        try {
            $path = Storage::path($this->import->file_path);

            if (! is_file($path)) {
                $this->failImport('Import file was not found.');
                return;
            }

            $handle = fopen($path, 'r');
            if ($handle === false) {
                $this->failImport('Unable to open CSV file.');
                return;
            }

            $rawHeaders = fgetcsv($handle);
            if ($rawHeaders === false) {
                fclose($handle);
                $this->failImport('CSV file is empty.');
                return;
            }

            $headers = array_map(
                fn ($header) => Str::of((string) $header)->lower()->trim()->value(),
                $rawHeaders
            );

            $requiredHeaders = ['type', 'description'];
            $missingHeaders = array_values(array_diff($requiredHeaders, $headers));

            if (! empty($missingHeaders)) {
                fclose($handle);
                $this->failImport('Missing required header(s): '.implode(', ', $missingHeaders));
                return;
            }

            $line = 1; // header row

            while (($row = fgetcsv($handle)) !== false) {
                $line++;

                if ($this->isBlankRow($row)) {
                    continue;
                }

                $totalRows++;

                $rowData = $this->combineHeaders($headers, $row);
                $rowErrors = $this->validateRow($rowData);

                if (! empty($rowErrors)) {
                    $errors[] = [
                        'line' => $line,
                        'errors' => $rowErrors,
                    ];
                    continue;
                }

                try {
                    $this->createQuestionFromRow($rowData);
                    $processedRows++;
                } catch (\Throwable $exception) {
                    Log::warning('Question import row failed', [
                        'import_id' => $this->import->id,
                        'line' => $line,
                        'error' => $exception->getMessage(),
                    ]);

                    $errors[] = [
                        'line' => $line,
                        'errors' => ['Unexpected error while importing this row.'],
                    ];
                }
            }

            fclose($handle);

            $status = $processedRows > 0 ? 'completed' : (! empty($errors) ? 'failed' : 'completed');

            $this->import->update([
                'status' => $status,
                'total_rows' => $totalRows,
                'processed_rows' => $processedRows,
                'error_log' => ! empty($errors) ? $errors : null,
            ]);
        } catch (\Throwable $exception) {
            Log::error('Question import processing failed', [
                'import_id' => $this->import->id,
                'error' => $exception->getMessage(),
            ]);

            $this->failImport('Import failed due to an unexpected error.');
        }
    }

    private function failImport(string $message): void
    {
        $this->import->update([
            'status' => 'failed',
            'error_log' => [[
                'line' => 1,
                'errors' => [$message],
            ]],
        ]);
    }

    private function isBlankRow(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    private function combineHeaders(array $headers, array $row): array
    {
        if (count($row) < count($headers)) {
            $row = array_pad($row, count($headers), null);
        }

        return array_combine($headers, array_slice($row, 0, count($headers))) ?: [];
    }

    private function validateRow(array $row): array
    {
        $normalized = [
            'type' => Str::of((string) ($row['type'] ?? ''))->lower()->trim()->value(),
            'title' => trim((string) ($row['title'] ?? '')),
            'description' => trim((string) ($row['description'] ?? '')),
            'difficulty' => $this->emptyToNull(Str::of((string) ($row['difficulty'] ?? ''))->lower()->trim()->value()),
            'explanation' => trim((string) ($row['explanation'] ?? '')),
            'word_limit' => $this->emptyToNull(trim((string) ($row['word_limit'] ?? ''))),
            'marks_default' => $this->emptyToNull(trim((string) ($row['marks_default'] ?? ''))),
            'options' => (string) ($row['options'] ?? ''),
            'correct_options' => (string) ($row['correct_options'] ?? ''),
            'tags' => (string) ($row['tags'] ?? ''),
        ];

        $validator = Validator::make($normalized, [
            'type' => ['required', Rule::in(['mcq', 'free_text'])],
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'difficulty' => ['nullable', Rule::in(['easy', 'medium', 'hard'])],
            'explanation' => ['nullable', 'string'],
            'word_limit' => ['nullable', 'integer', 'min:1'],
            'marks_default' => ['nullable', 'integer', 'min:1'],
            'options' => ['nullable', 'string'],
            'correct_options' => ['nullable', 'string'],
            'tags' => ['nullable', 'string'],
        ]);

        $errors = $validator->errors()->all();

        if ($normalized['type'] === 'mcq') {
            $options = $this->parseMcqOptions($normalized);

            if (count($options) < 2) {
                $errors[] = 'MCQ rows must include at least two options in the options column.';
            }

            $correctCount = collect($options)->where('is_correct', true)->count();
            if ($correctCount < 1) {
                $errors[] = 'MCQ rows must include at least one correct option.';
            }
        }

        return $errors;
    }

    private function createQuestionFromRow(array $row): void
    {
        $type = Str::of((string) ($row['type'] ?? ''))->lower()->trim()->value();
        $difficulty = Str::of((string) ($row['difficulty'] ?? 'medium'))->lower()->trim()->value();
        $difficulty = in_array($difficulty, ['easy', 'medium', 'hard'], true) ? $difficulty : 'medium';
        $marksDefault = (int) ($row['marks_default'] ?? 1);
        $marksDefault = $marksDefault > 0 ? $marksDefault : 1;

        $question = Question::create([
            'organization_id' => $this->import->organization_id,
            'type' => $type,
            'title' => trim((string) ($row['title'] ?? '')) ?: null,
            'description' => trim((string) ($row['description'] ?? '')),
            'difficulty' => $difficulty,
            'explanation' => trim((string) ($row['explanation'] ?? '')) ?: null,
            'word_limit' => $type === 'free_text' && trim((string) ($row['word_limit'] ?? '')) !== ''
                ? (int) $row['word_limit']
                : null,
            'marks_default' => $marksDefault,
            'status' => 'draft',
            'created_by' => $this->import->imported_by,
            'updated_by' => $this->import->imported_by,
        ]);

        if ($type === 'mcq') {
            $question->options()->createMany($this->parseMcqOptions($row));
        }

        $tags = collect(explode(',', (string) ($row['tags'] ?? '')))
            ->map(fn ($tag) => trim($tag))
            ->filter()
            ->unique()
            ->values();

        if ($tags->isNotEmpty()) {
            $tagIds = $tags->map(function ($tagName) {
                return Tag::firstOrCreate(['name' => $tagName])->id;
            });

            $question->tags()->sync($tagIds);
        }
    }

    private function parseMcqOptions(array $row): array
    {
        $rawOptions = collect(explode('|', (string) ($row['options'] ?? '')))
            ->map(fn ($value) => trim($value))
            ->filter(fn ($value) => $value !== '')
            ->values();

        $correctFromIndexes = $this->parseCorrectOptionIndexes((string) ($row['correct_options'] ?? ''));

        return $rawOptions
            ->map(function ($optionText, $index) use ($correctFromIndexes) {
                $isCorrect = false;

                if (str_starts_with($optionText, '*')) {
                    $isCorrect = true;
                    $optionText = ltrim(substr($optionText, 1));
                }

                if (in_array($index + 1, $correctFromIndexes, true)) {
                    $isCorrect = true;
                }

                return [
                    'option_text' => $optionText,
                    'is_correct' => $isCorrect,
                    'order' => $index + 1,
                ];
            })
            ->all();
    }

    private function parseCorrectOptionIndexes(string $value): array
    {
        if (trim($value) === '') {
            return [];
        }

        return collect(explode(',', $value))
            ->map(fn ($index) => (int) trim($index))
            ->filter(fn ($index) => $index > 0)
            ->unique()
            ->values()
            ->all();
    }

    private function emptyToNull(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return trim($value) === '' ? null : $value;
    }
}
