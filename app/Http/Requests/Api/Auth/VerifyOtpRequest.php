<?php

namespace App\Http\Requests\Api\Auth;

use Illuminate\Foundation\Http\FormRequest;

class VerifyOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'max:20'],
            'code' => ['required', 'string', 'min:6', 'max:7'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required' => 'Le numéro de téléphone est requis.',
            'code.required' => 'Le code de vérification est requis.',
            'code.min' => 'Le code de vérification doit contenir 6 chiffres.',
        ];
    }

    public function normalizedCode(): string
    {
        return str_replace('-', '', $this->code);
    }
}
