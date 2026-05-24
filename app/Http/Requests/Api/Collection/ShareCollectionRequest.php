<?php

namespace App\Http\Requests\Api\Collection;

use App\Services\SmsService;
use Illuminate\Foundation\Http\FormRequest;

class ShareCollectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('recipientPhone')) {
            $this->merge([
                'recipientPhone' => SmsService::normalizePhone($this->recipientPhone),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'recipientPhone' => ['required', 'string', 'max:20'],
            'permissions' => ['required', 'in:view,edit'],
        ];
    }

    public function messages(): array
    {
        return [
            'recipientPhone.required' => 'Le numéro de téléphone du destinataire est requis.',
            'recipientPhone.string' => 'Le numéro de téléphone n\'est pas valide.',
            'permissions.required' => 'Les permissions sont requises.',
            'permissions.in' => 'Les permissions doivent être "view" ou "edit".',
        ];
    }
}
