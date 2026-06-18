<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Laravel\Fortify\Fortify;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            Fortify::username() => ['required', 'string'],
            'password' => ['nullable', 'string', 'required_without:pin_code'],
            'pin_code' => ['nullable', 'string', 'size:6', 'required_without:password'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'password.required_without' => 'Le mot de passe ou le code PIN est requis.',
            'pin_code.required_without' => 'Le code PIN ou le mot de passe est requis.',
            'pin_code.size' => 'Le code PIN doit contenir 6 chiffres.',
        ];
    }
}
