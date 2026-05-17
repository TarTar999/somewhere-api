<?php

namespace App\Http\Requests\Api\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ChangePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'oldPassword' => ['required', 'string', 'current_password'],
            'newPassword' => ['required', 'string', Password::min(6), 'different:oldPassword'],
        ];
    }

    public function messages(): array
    {
        return [
            'oldPassword.required' => 'L\'ancien mot de passe est requis.',
            'oldPassword.current_password' => 'L\'ancien mot de passe est incorrect.',
            'newPassword.required' => 'Le nouveau mot de passe est requis.',
            'newPassword.different' => 'Le nouveau mot de passe doit être différent de l\'ancien.',
        ];
    }
}
