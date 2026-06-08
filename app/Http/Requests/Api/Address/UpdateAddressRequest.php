<?php

namespace App\Http\Requests\Api\Address;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'displayName' => ['sometimes', 'string', 'max:255'],
            'houseType' => ['sometimes', 'in:immeuble,villa,maison,studio,bureau,terrain,autre'],
            'homeStatus' => ['sometimes', 'in:locataire,residence,familiale,proprietaire,commercial,non_bati'],
            'quarter' => ['sometimes', 'string', 'max:255'],
            'subQuarter' => ['sometimes', 'nullable', 'string', 'max:255'],
            'lieuDit' => ['sometimes', 'nullable', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'residentName' => ['sometimes', 'nullable', 'string', 'max:255'],
            'isNonHabitation' => ['sometimes', 'boolean'],
        ];
    }
}
