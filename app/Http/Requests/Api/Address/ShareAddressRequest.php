<?php

namespace App\Http\Requests\Api\Address;

use Illuminate\Foundation\Http\FormRequest;

class ShareAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'recipientEmail' => ['required', 'email'],
        ];
    }

    public function messages(): array
    {
        return [
            'recipientEmail.required' => 'L\'adresse email du destinataire est requise.',
            'recipientEmail.email' => 'L\'adresse email n\'est pas valide.',
        ];
    }
}
