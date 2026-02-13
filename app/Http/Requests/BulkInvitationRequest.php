<?php

namespace App\Http\Requests;

use App\Models\Invitation;
use Illuminate\Foundation\Http\FormRequest;

class BulkInvitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('createBulk', Invitation::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'test_id' => ['required', 'uuid', 'exists:tests,id'],
            'candidates' => ['required', 'array', 'min:1'],
            'candidates.*.email' => ['required', 'email'],
            'candidates.*.name' => ['nullable', 'string', 'max:255'],
        ];
    }
}
