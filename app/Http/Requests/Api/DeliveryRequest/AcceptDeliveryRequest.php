<?php

namespace App\Http\Requests\Api\DeliveryRequest;

use Illuminate\Foundation\Http\FormRequest;

class AcceptDeliveryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'address_id' => ['required_without:location', 'nullable', 'exists:addresses,id'],
            'location' => ['required_without:address_id', 'nullable', 'array'],
            'location.latitude' => ['required_with:location', 'numeric', 'between:-90,90'],
            'location.longitude' => ['required_with:location', 'numeric', 'between:-180,180'],
        ];
    }

    public function messages(): array
    {
        return [
            'address_id.required_without' => 'Une adresse ou une localisation est requise.',
            'address_id.exists' => 'L\'adresse n\'existe pas.',
            'location.required_without' => 'Une localisation ou une adresse est requise.',
            'location.latitude.required_with' => 'La latitude est requise.',
            'location.latitude.between' => 'La latitude doit etre entre -90 et 90.',
            'location.longitude.required_with' => 'La longitude est requise.',
            'location.longitude.between' => 'La longitude doit etre entre -180 et 180.',
        ];
    }
}
