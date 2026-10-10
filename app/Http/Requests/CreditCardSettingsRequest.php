<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreditCardSettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'credit_opened_at' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
            'credit_opened_precision' => ['sometimes', 'required', Rule::in(['day', 'month'])],
            'closed_at' => ['nullable', 'date', 'after_or_equal:credit_opened_at', 'before_or_equal:today'],
            'credit_rewards' => ['nullable', 'array:points,spend,point_value,category_rates'],
            'credit_rewards.points' => ['nullable', 'numeric', 'gt:0', 'required_with:credit_rewards.spend'],
            'credit_rewards.spend' => ['nullable', 'numeric', 'gt:0', 'required_with:credit_rewards.points'],
            'credit_rewards.point_value' => ['nullable', 'numeric', 'min:0'],
            'credit_rewards.category_rates' => ['nullable', 'array'],
            'credit_rewards.category_rates.*' => ['array:category_id,points,spend'],
            'credit_rewards.category_rates.*.category_id' => ['required', 'integer', 'distinct', Rule::exists('categories', 'id')->where('team_id', $this->user()?->current_team_id)],
            'credit_rewards.category_rates.*.points' => ['required', 'numeric', 'min:0'],
            'credit_rewards.category_rates.*.spend' => ['required', 'numeric', 'gt:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'closed_at.after_or_equal' => 'La fecha de cierre debe ser posterior a la apertura.',
            'credit_rewards.spend.gt' => 'El importe por punto debe ser mayor que cero.',
            'credit_rewards.points.gt' => 'La cantidad de puntos debe ser mayor que cero.',
            'credit_opened_at.before_or_equal' => 'La apertura no puede estar en el futuro.',
        ];
    }
}
