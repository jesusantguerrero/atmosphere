<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Plan\Entities\PlanTypes;

class UpdateShoppingItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (int) $this->route('plan')->team_id === (int) $this->user()?->current_team_id;
    }

    public function rules(): array
    {
        $plan = $this->route('plan');

        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'stage_id' => ['sometimes', 'integer'],
            'plan_id' => [
                'sometimes',
                'integer',
                Rule::exists('plans', 'id')
                    ->where('team_id', $plan->team_id)
                    ->where('plan_type_name', PlanTypes::SHOPPING_LIST->value),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'title.max' => 'El nombre admite hasta 255 caracteres.',
            'plan_id.exists' => 'La lista de destino no existe.',
        ];
    }
}
