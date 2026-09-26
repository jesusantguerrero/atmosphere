<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecategorizeTransactionLineRequest extends FormRequest
{
    public function authorize(): bool
    {
        $line = $this->route('line');

        return $line && (int) $line->team_id === (int) $this->user()->current_team_id;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'category_id' => [
                'required',
                'integer',
                Rule::exists('categories', 'id')->where('team_id', $this->user()->current_team_id),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'category_id.required' => 'Pick a category for this transaction.',
            'category_id.exists' => 'That category does not belong to this team.',
        ];
    }
}
