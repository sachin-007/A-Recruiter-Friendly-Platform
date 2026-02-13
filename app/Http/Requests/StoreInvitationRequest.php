<?php

namespace App\Http\Requests;

use App\Models\Invitation;
use Illuminate\Foundation\Http\FormRequest;

class StoreInvitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Invitation::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'test_id' => ['required', 'uuid', 'exists:tests,id'],
            'candidate_email' => ['required', 'email'],
            'candidate_name' => ['nullable', 'string', 'max:255'],
        ];
    }
}
