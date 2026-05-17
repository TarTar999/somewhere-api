<?php

namespace App\Http\Requests\Api\DeliveryRequest;

use Illuminate\Foundation\Http\FormRequest;

class StoreDeliveryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'value' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'in:XAF,EUR,USD'],
            'pickup_address_id' => ['nullable', 'exists:addresses,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Le titre est requis.',
            'title.max' => 'Le titre ne peut pas depasser 255 caracteres.',
            'value.required' => 'La valeur est requise.',
            'value.numeric' => 'La valeur doit etre un nombre.',
            'value.min' => 'La valeur ne peut pas etre negative.',
            'currency.required' => 'La devise est requise.',
            'currency.in' => 'La devise doit etre XAF, EUR ou USD.',
            'pickup_address_id.exists' => 'L\'adresse de ramassage n\'existe pas.',
        ];
    }
}
