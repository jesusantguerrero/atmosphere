<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SharedShoppingItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255', 'not_regex:/^\\s*$/u'],
            'stage_id' => ['nullable', 'integer'],
            'category_name' => ['nullable', 'string', 'max:120', 'not_regex:/^\\s*$/u'],
        ];
    }

    public function messages(): array
    {
        return ['title.required' => 'Escribe el nombre del producto.', 'title.not_regex' => 'Escribe el nombre del producto.', 'category_name.max' => 'La categoría admite hasta 120 caracteres.', 'category_name.not_regex' => 'Escribe el nombre de la categoría.'];
    }
}
