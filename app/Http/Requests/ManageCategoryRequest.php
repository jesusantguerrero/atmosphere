<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ManageCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        if ($this->routeIs('category-manager.preview', 'category-manager.merge')) {
            return [
                'target_id' => ['required', 'integer'],
                'goal' => ['nullable', 'in:source,target'],
                'token' => [$this->routeIs('category-manager.merge') ? 'required' : 'nullable', 'string', 'size:64'],
            ];
        }

        return [
            'name' => [$this->isMethod('POST') ? 'required' : 'sometimes', 'required', 'string', 'max:255'],
            'parent_id' => ['sometimes', 'nullable', 'integer'],
            'archive' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Escribe un nombre.',
            'name.max' => 'El nombre debe tener como máximo 255 caracteres.',
            'target_id.required' => 'Elige la categoría que conservarás.',
            'token.required' => 'Revisa la vista previa antes de unificar.',
        ];
    }
}
