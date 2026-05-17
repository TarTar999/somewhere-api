<?php

namespace App\Http\Requests\Api\Collection;

use Illuminate\Foundation\Http\FormRequest;

class AddAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'addressId' => ['required', 'exists:addresses,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'addressId.required' => 'L\'identifiant de l\'adresse est requis.',
            'addressId.exists' => 'Cette adresse n\'existe pas.',
        ];
    }
}
