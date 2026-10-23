<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateShoppingVisitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'payee_id' => ['present', 'nullable', 'integer', Rule::exists('payees', 'id')->where('team_id', $this->route('plan')->team_id)],
        ];
    }

    public function messages(): array
    {
        return [
            'payee_id.present' => 'Indica la tienda o null para quitarla.',
            'payee_id.exists' => 'La tienda seleccionada no existe.',
        ];
    }
}
