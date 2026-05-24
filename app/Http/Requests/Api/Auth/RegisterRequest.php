<?php

namespace App\Http\Requests\Api\Auth;

use App\Services\SmsService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Normalize phone number before validation
        if ($this->has('phone')) {
            $this->merge([
                'phone' => SmsService::normalizePhone($this->phone),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'max:20', 'unique:users,phone'],
            'firstName' => ['required', 'string', 'max:255'],
            'lastName' => ['required', 'string', 'max:255'],
            'civility' => ['nullable', 'in:male,female'],
            'cni' => ['nullable', 'string', 'max:50'],
            'nui' => ['nullable', 'string', 'max:50'],
            'cniExpiration' => ['nullable', 'date'],
            'email' => ['nullable', 'email', 'unique:users,email'],
            'password' => ['required', 'string', Password::min(6)],
            'quartier' => ['nullable', 'string', 'max:255'],
            'sousQuartier' => ['nullable', 'string', 'max:255'],
            'lieuDit' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required' => 'Le numéro de téléphone est requis.',
            'phone.unique' => 'Ce numéro de téléphone est déjà utilisé.',
            'firstName.required' => 'Le prénom est requis.',
            'lastName.required' => 'Le nom est requis.',
            'email.unique' => 'Cette adresse email est déjà utilisée.',
            'password.required' => 'Le mot de passe est requis.',
        ];
    }
}
