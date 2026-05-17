<?php

namespace App\Http\Requests\Api\Collection;

use Illuminate\Foundation\Http\FormRequest;

class StoreCollectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'icon' => ['nullable', 'string', 'max:50'],
            'color' => ['nullable', 'string', 'max:7', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'type' => ['sometimes', 'in:system,custom,delivery'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:collections,slug'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Le nom de la collection est requis.',
            'color.regex' => 'La couleur doit être au format hexadécimal (#RRGGBB).',
            'slug.unique' => 'Ce slug est déjà utilisé.',
        ];
    }
}
