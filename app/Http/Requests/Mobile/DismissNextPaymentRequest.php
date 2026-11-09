<?php

namespace App\Http\Requests\Mobile;

use Illuminate\Foundation\Http\FormRequest;

class DismissNextPaymentRequest extends FormRequest
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
            'payment_id' => ['required', 'string', 'max:191'],
            'period' => ['required', 'date_format:Y-m'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'payment_id.required' => __('Choose the payment to dismiss.'),
            'period.required' => __('Choose the month to dismiss the payment for.'),
            'period.date_format' => __('The month must use the YYYY-MM format.'),
        ];
    }
}
