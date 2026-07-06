<?php

namespace App\Http\Requests\Api\Auth;

use Illuminate\Foundation\Http\FormRequest;

class SocialAuthRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_token' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:255'],
            'device_id' => ['nullable', 'string', 'max:255'],
            // Apple-specific: user data is only sent on first authorization
            'user' => ['nullable', 'array'],
            'user.firstName' => ['nullable', 'string', 'max:255'],
            'user.lastName' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_token.required' => 'The ID token is required.',
            'id_token.string' => 'The ID token must be a string.',
        ];
    }

    /**
     * Get Apple user data if provided (first-time auth only).
     */
    public function appleUserData(): ?array
    {
        $user = $this->input('user');

        if (!$user) {
            return null;
        }

        return [
            'firstName' => $user['firstName'] ?? null,
            'lastName' => $user['lastName'] ?? null,
        ];
    }
}
