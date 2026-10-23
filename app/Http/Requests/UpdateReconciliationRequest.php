<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateReconciliationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (int) $this->route('reconciliation')->team_id === (int) $this->user()?->current_team_id;
    }

    public function rules(): array
    {
        $reconciliation = $this->route('reconciliation');

        return [
            'balance' => ['required_without:date', 'nullable', 'numeric'],
            'date' => [
                'required_without:balance',
                'nullable',
                'date_format:Y-m-d',
                Rule::unique('reconciliations', 'date')
                    ->where('account_id', $reconciliation->account_id)
                    ->ignore($reconciliation->id),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'balance.required_without' => 'Indica el balance o la fecha.',
            'date.required_without' => 'Indica el balance o la fecha.',
            'date.unique' => 'Ya existe una conciliación de esta cuenta en esa fecha.',
        ];
    }
}
