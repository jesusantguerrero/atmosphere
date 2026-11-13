<?php

namespace App\Http\Requests;

use App\Domains\Housing\Contracts\ChoreArea;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateChoreAreaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (int) $this->route('item')->team_id === (int) $this->user()?->current_team_id;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'area' => ['present', 'nullable', 'string', Rule::enum(ChoreArea::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'area.present' => 'Indica el área de la tarea.',
            'area.enum' => 'El área seleccionada no existe.',
        ];
    }
}
