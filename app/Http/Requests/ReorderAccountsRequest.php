<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReorderAccountsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        return $user && $user->currentTeam && $user->belongsToTeam($user->currentTeam);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'account_ids' => ['required', 'array', 'list', 'min:1', 'max:500'],
            'account_ids.*' => ['required', 'integer', 'distinct', Rule::exists('accounts', 'id')
                ->where('team_id', $this->user()->current_team_id)],
        ];
    }

    public function messages(): array
    {
        return [
            'account_ids.required' => 'Selecciona las cuentas que quieres ordenar.',
            'account_ids.array' => 'El orden debe ser una lista de cuentas.',
            'account_ids.list' => 'El orden debe ser una lista consecutiva.',
            'account_ids.min' => 'Selecciona al menos una cuenta.',
            'account_ids.max' => 'Puedes ordenar hasta 500 cuentas a la vez.',
            'account_ids.*.required' => 'Cada cuenta debe tener un identificador.',
            'account_ids.*.integer' => 'El identificador de la cuenta no es válido.',
            'account_ids.*.distinct' => 'Una cuenta no puede aparecer dos veces.',
            'account_ids.*.exists' => 'Solo puedes ordenar cuentas de tu espacio actual.',
        ];
    }
}
