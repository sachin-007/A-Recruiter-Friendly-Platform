<?php

namespace App\Http\Requests;

use App\Models\Organization;
use Illuminate\Foundation\Http\FormRequest;

class StoreOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Organization::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:organizations,name'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'settings' => ['nullable', 'array'],
            'settings.notifications' => ['nullable', 'array'],
            'settings.notifications.email' => ['nullable', 'boolean'],
            'settings.notifications.sms' => ['nullable', 'boolean'],
            'settings.default_role_permissions' => ['prohibited'],
        ];
    }
}
