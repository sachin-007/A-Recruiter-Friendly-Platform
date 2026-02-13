<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', User::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                Rule::unique('users', 'email')->where(function ($query) {
                    return $query->where('organization_id', $this->user()?->organization_id);
                }),
            ],
            'role' => ['required', Rule::in(['admin', 'recruiter', 'author'])],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
