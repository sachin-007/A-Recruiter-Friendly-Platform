<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $organization = $this->user()?->organization;
        return $organization ? ($this->user()?->can('update', $organization) ?? false) : false;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'settings' => ['nullable', 'array'],
            'settings.notifications' => ['nullable', 'array'],
            'settings.notifications.email' => ['nullable', 'boolean'],
            'settings.notifications.sms' => ['nullable', 'boolean'],
            'settings.default_role_permissions' => ['prohibited'],
        ];
    }
}
