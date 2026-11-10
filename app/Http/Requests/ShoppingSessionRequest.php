<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ShoppingSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return $this->routeIs('shopping.sessions.finish') ? [
            'session_id' => ['required', 'uuid'],
            'total' => ['required', 'numeric', 'min:0', 'max:999999999.99', 'decimal:0,2'],
            'currency' => ['required', 'in:DOP,USD,EUR'],
            'note' => ['nullable', 'string', 'max:2000'],
            'payee_id' => ['nullable', 'integer', Rule::exists('payees', 'id')->where('team_id', $this->route('plan')->team_id)],
            'account_id' => ['nullable', 'integer', Rule::exists('accounts', 'id')->where('team_id', $this->route('plan')->team_id)],
            'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')->where('team_id', $this->route('plan')->team_id)],
            'continue' => ['sometimes', 'boolean'],
        ] : [];
    }

    public function messages(): array
    {
        return ['total.required' => 'Indica el importe de esta compra.', 'total.decimal' => 'Usa hasta dos decimales.', 'total.min' => 'El importe no puede ser negativo.', 'session_id.required' => 'Actualiza la sesión de compra.', 'note.max' => 'La nota admite hasta 2000 caracteres.', 'payee_id.exists' => 'La tienda seleccionada no existe.', 'account_id.exists' => 'La cuenta seleccionada no existe.', 'category_id.exists' => 'La categoría seleccionada no existe.'];
    }
}
