<?php

namespace App\Http\Requests\Api\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('user');

        return [
            'firstName' => ['sometimes', 'string', 'max:255'],
            'lastName' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', Rule::unique('users')->ignore($userId)],
            'phone' => ['sometimes', 'string', 'max:20'],
            'sex' => ['sometimes', 'nullable', 'in:male,female'],
            'nuiNumber' => ['sometimes', 'nullable', 'string', 'max:50'],
            'cniNumber' => ['sometimes', 'nullable', 'string', 'max:50'],
            'cniExpirationDate' => ['sometimes', 'nullable', 'date'],
            'settings' => ['sometimes', 'array'],
            'settings.language' => ['sometimes', 'string', 'max:10'],
            'settings.unit' => ['sometimes', 'in:metric,imperial'],
            'settings.notifications' => ['sometimes', 'in:enabled,disabled'],
            'settings.mapType' => ['sometimes', 'in:ApplePlan,GoogleMap'],
            'settings.googleSearch' => ['sometimes', 'boolean'],
            'settings.isCityMapper' => ['sometimes', 'boolean'],
            'settings.darkMode' => ['sometimes', 'boolean'],
        ];
    }
}
