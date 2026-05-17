<?php

namespace App\Http\Requests\Api\Collection;

use Illuminate\Foundation\Http\FormRequest;

class ShareCollectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'recipientEmail' => ['required', 'email'],
            'permissions' => ['required', 'in:view,edit'],
        ];
    }

    public function messages(): array
    {
        return [
            'recipientEmail.required' => 'L\'adresse email du destinataire est requise.',
            'recipientEmail.email' => 'L\'adresse email n\'est pas valide.',
            'permissions.required' => 'Les permissions sont requises.',
            'permissions.in' => 'Les permissions doivent être "view" ou "edit".',
        ];
    }
}
