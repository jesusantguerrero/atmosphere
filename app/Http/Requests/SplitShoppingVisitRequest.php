<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SplitShoppingVisitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'item_ids' => ['required', 'array', 'min:1'],
            'item_ids.*' => ['integer', 'distinct'],
            'total' => ['required', 'numeric', 'gt:0', 'max:999999999.99', 'decimal:0,2'],
            'payee_id' => ['nullable', 'integer', Rule::exists('payees', 'id')->where('team_id', $this->route('plan')->team_id)],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'item_ids.required' => 'Elige al menos un producto para la nueva visita.',
            'item_ids.min' => 'Elige al menos un producto para la nueva visita.',
            'item_ids.*.integer' => 'Los productos elegidos no son válidos.',
            'item_ids.*.distinct' => 'Hay productos repetidos.',
            'total.required' => 'Indica el importe de la nueva visita.',
            'total.gt' => 'El importe debe ser mayor que cero.',
            'total.decimal' => 'Usa hasta dos decimales.',
            'payee_id.exists' => 'La tienda seleccionada no existe.',
            'note.max' => 'La nota admite hasta 2000 caracteres.',
        ];
    }
}
