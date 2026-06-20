<?php

namespace App\Http\Requests\Api\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'max:20'],
            'password' => ['nullable', 'string', 'min:6'],
            'pin_code' => ['nullable', 'string', 'size:6'],
            'device_name' => ['nullable', 'string', 'max:255'],
            'device_id' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required' => 'Le numéro de téléphone est requis.',
            'password.min' => 'Le mot de passe doit contenir au moins 6 caractères.',
            'pin_code.size' => 'Le code PIN doit contenir exactement 6 chiffres.',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (!$this->filled('password') && !$this->filled('pin_code')) {
                $validator->errors()->add('credentials', 'Le mot de passe ou le code PIN est requis.');
            }
        });
    }
}
