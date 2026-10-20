<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SharedShoppingToggleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['state' => ['nullable', 'in:pending,buy,skip'], 'reviewed' => ['sometimes', 'boolean']];
    }

    public function messages(): array
    {
        return ['state.in' => 'Selecciona un estado válido.', 'reviewed.boolean' => 'El estado de revisión no es válido.'];
    }
}
