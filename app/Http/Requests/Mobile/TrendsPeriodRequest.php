<?php

namespace App\Http\Requests\Mobile;

use Illuminate\Foundation\Http\FormRequest;

class TrendsPeriodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'start' => ['nullable', 'date_format:Y-m-d'],
            'end' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start'],
            'account_ids' => ['nullable', 'array'],
            'account_ids.*' => ['integer'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'start.date_format' => __('The start date must use the YYYY-MM-DD format.'),
            'end.date_format' => __('The end date must use the YYYY-MM-DD format.'),
            'end.after_or_equal' => __('The end date cannot be before the start date.'),
            'account_ids.*.integer' => __('Choose valid accounts.'),
        ];
    }
}
