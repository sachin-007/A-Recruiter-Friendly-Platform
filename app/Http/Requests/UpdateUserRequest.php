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
        $actor = $this->user();
        $isSuperAdmin = $actor?->role === 'super_admin';
        $organizationId = $isSuperAdmin
            ? ($this->input('organization_id') ?: $target?->organization_id)
            : $actor?->organization_id;
        $assignableRoles = $isSuperAdmin
            ? ['super_admin', 'admin', 'recruiter', 'author']
            : ['admin', 'recruiter', 'author'];

        return [
            'organization_id' => [$isSuperAdmin ? 'sometimes' : 'prohibited', 'uuid', 'exists:organizations,id'],
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'email',
                Rule::unique('users', 'email')
                    ->where(function ($query) use ($organizationId) {
                        return $query->where('organization_id', $organizationId);
                    })
                    ->ignore($target?->id),
            ],
            'role' => ['sometimes', Rule::in($assignableRoles)],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
