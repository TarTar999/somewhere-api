<?php

namespace App\Http\Requests\Api\Address;

use Illuminate\Foundation\Http\FormRequest;

class StoreAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric', 'min:0'],
            'houseType' => ['required', 'in:immeuble,villa,maison,studio,bureau,autre'],
            'homeStatus' => ['required', 'in:locataire,residence,familiale,proprietaire,commercial'],
            'quarter' => ['required', 'string', 'max:255'],
            'subQuarter' => ['nullable', 'string', 'max:255'],
            'lieuDit' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            // Street data (optional - if not provided, will be fetched from Nominatim)
            'streetOsmId' => ['nullable', 'integer'],
            'streetData' => ['nullable', 'array'],
            'streetData.osm_id' => ['required_with:streetData', 'integer'],
            'streetData.osm_type' => ['nullable', 'string'],
            'streetData.display_name' => ['required_with:streetData', 'string'],
            'streetData.address' => ['nullable', 'array'],
            'streetData.geojson' => ['nullable', 'array'],
            'streetData.boundingbox' => ['nullable', 'array'],
            'honorDeclaration' => ['required', 'accepted'],
            'signature' => ['required', 'string'],
            'video' => ['nullable', 'file', 'mimes:mp4,mov,avi,webm,3gp', 'mimetypes:video/mp4,video/quicktime,video/avi,video/webm,video/3gpp', 'max:50000'],
            // Domiciliation options
            'domiciliationName' => ['nullable', 'string', 'max:100'],
            'isPrimary' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'latitude.required' => 'La latitude est requise.',
            'longitude.required' => 'La longitude est requise.',
            'houseType.required' => 'Le type d\'habitation est requis.',
            'homeStatus.required' => 'Le statut d\'occupation est requis.',
            'quarter.required' => 'Le quartier est requis.',
            'honorDeclaration.accepted' => 'Vous devez accepter la déclaration sur l\'honneur.',
            'signature.required' => 'La signature est requise.',
            'video.max' => 'La vidéo ne doit pas dépasser 100 Mo.',
        ];
    }
}
