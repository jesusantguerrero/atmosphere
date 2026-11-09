<?php

namespace App\Http\Requests\Mobile;

use Illuminate\Foundation\Http\FormRequest;

class MeBriefRequest extends FormRequest
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
            'min_delta' => ['nullable', 'numeric', 'min:0'],
            'min_pct' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'min_delta.numeric' => __('The minimum difference must be a number.'),
            'min_delta.min' => __('The minimum difference cannot be negative.'),
            'min_pct.numeric' => __('The minimum percentage must be a number.'),
            'min_pct.min' => __('The minimum percentage cannot be negative.'),
        ];
    }
}
