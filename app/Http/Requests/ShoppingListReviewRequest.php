<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShoppingListReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->routeIs('shared.list.review.confirm') || $this->user() !== null;
    }

    public function rules(): array
    {
        return $this->routeIs('shared.list.review.confirm')
            ? ['note' => ['nullable', 'string', 'max:2000'], 'current_version' => ['required', 'string', 'size:64']]
            : ['review_id' => ['required', 'uuid']];
    }

    public function messages(): array
    {
        return ['note.max' => 'La nota debe tener como máximo 2000 caracteres.', 'current_version.required' => 'Actualiza la lista antes de confirmar.', 'review_id.required' => 'Selecciona la revisión de esta compra.'];
    }
}
