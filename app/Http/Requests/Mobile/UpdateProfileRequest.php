<?php

namespace App\Http\Requests\Mobile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
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
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users')->ignore($this->user()->id)],
            'photo' => ['sometimes', 'nullable', 'file', 'mimes:jpg,jpeg,png', 'max:1024'],
            'language' => ['sometimes', 'nullable', 'string', Rule::in(['en', 'es'])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.unique' => __('This email address is already in use.'),
            'photo.mimes' => __('The photo must be a JPG or PNG image.'),
            'photo.max' => __('The photo may not be larger than 1 MB.'),
        ];
    }
}
