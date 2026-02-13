<?php

namespace App\Http\Requests;

use App\Models\Import;
use Illuminate\Foundation\Http\FormRequest;

class CsvImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Import::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimetypes:text/plain,text/csv,text/tsv', 'max:5120'],
        ];
    }
}
