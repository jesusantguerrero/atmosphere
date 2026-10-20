<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SharedShoppingCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['stage_id' => ['required', 'integer'], 'quantity' => ['nullable', 'string', 'max:60'], 'note' => ['nullable', 'string', 'max:500']];
    }

    public function messages(): array
    {
        return ['quantity.max' => 'La cantidad admite hasta 60 caracteres.', 'note.max' => 'La indicación admite hasta 500 caracteres.', 'stage_id.required' => 'Selecciona una categoría.', 'stage_id.integer' => 'Selecciona una categoría válida.'];
    }
}
