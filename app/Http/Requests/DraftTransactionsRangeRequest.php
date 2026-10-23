<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DraftTransactionsRangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'start' => ['nullable', 'date_format:Y-m-d'],
            'end' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start'],
        ];
    }

    public function messages(): array
    {
        return [
            'start.date_format' => 'La fecha de inicio debe tener el formato AAAA-MM-DD.',
            'end.date_format' => 'La fecha final debe tener el formato AAAA-MM-DD.',
            'end.after_or_equal' => 'La fecha final no puede ser anterior a la inicial.',
        ];
    }
}
