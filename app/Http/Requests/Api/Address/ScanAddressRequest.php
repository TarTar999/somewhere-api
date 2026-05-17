<?php

namespace App\Http\Requests\Api\Address;

use Illuminate\Foundation\Http\FormRequest;

class ScanAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'qrData' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'qrData.required' => 'Les données du QR code sont requises.',
        ];
    }
}
