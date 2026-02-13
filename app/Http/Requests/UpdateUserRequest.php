<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var \App\Models\User|null $target */
        $target = $this->route('user');
        return $target ? ($this->user()?->can('update', $target) ?? false) : false;
    }

    public function rules(): array
    {
        /** @var \App\Models\User|null $target */
        $target = $this->route('user');

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'email',
                Rule::unique('users', 'email')
                    ->where(function ($query) {
                        return $query->where('organization_id', $this->user()?->organization_id);
                    })
                    ->ignore($target?->id),
            ],
            'role' => ['sometimes', Rule::in(['admin', 'recruiter', 'author'])],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
