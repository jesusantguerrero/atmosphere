<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BudgetTargetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['nullable', 'numeric'],
            'principal' => ['nullable', 'numeric', 'min:0'],
            'interest_rate' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'term_months' => ['nullable', 'numeric', 'min:0', 'max:1200'],
            'loan_start_date' => ['nullable', 'date'],
            'frequency_month_date' => ['required_if:target_type,loan', 'nullable', 'integer', 'between:1,31'],
            'notify' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'frequency_month_date.required_if' => __('Choose the monthly loan payment day.'),
            'frequency_month_date.between' => __('Choose a payment day between 1 and 31.'),
            'notify.boolean' => __('Choose whether to receive reminders.'),
        ];
    }
}
