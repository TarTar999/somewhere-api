<?php

namespace App\Http\Requests\Api\DeliveryRequest;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', 'in:in_progress,cancelled'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Le statut est requis.',
            'status.in' => 'Le statut doit etre in_progress ou cancelled.',
        ];
    }
}
