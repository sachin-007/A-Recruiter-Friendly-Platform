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
        $actor = $this->user();
        $isSuperAdmin = $actor?->role === 'super_admin';
        $organizationId = $isSuperAdmin ? $this->input('organization_id') : $actor?->organization_id;
        $assignableRoles = $isSuperAdmin
            ? ['super_admin', 'admin', 'recruiter', 'author']
            : ['admin', 'recruiter', 'author'];

        return [
            'organization_id' => [$isSuperAdmin ? 'required' : 'prohibited', 'uuid', 'exists:organizations,id'],
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                Rule::unique('users', 'email')->where(function ($query) use ($organizationId) {
                    return $query->where('organization_id', $organizationId);
                }),
            ],
            'role' => ['required', Rule::in($assignableRoles)],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
