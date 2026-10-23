<?php

namespace App\Http\Requests\Mobile;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNotificationPreferencesRequest extends FormRequest
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
            'channels' => ['required', 'array'],
            'channels.email' => ['sometimes', 'boolean'],
            'channels.push' => ['sometimes', 'boolean'],
        ];
    }
}
