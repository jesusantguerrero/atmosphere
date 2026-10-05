<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReconciliationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'balance' => ['required', 'numeric'],
            'date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'balance.required' => 'Enter the statement balance.',
            'balance.numeric' => 'The statement balance must be a number.',
            'date.required' => 'Choose the statement date.',
            'date.date_format' => 'The statement date must use YYYY-MM-DD.',
            'date.before_or_equal' => 'The statement date cannot be in the future.',
        ];
    }
}
