<?php

namespace App\Http\Requests\Mobile;

use App\Domains\Transaction\Services\StatementImportService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ImportStatementRequest extends FormRequest
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
            'file' => ['required', 'file', 'max:10240', 'extensions:'.implode(',', StatementImportService::EXTENSIONS)],
            'account_id' => ['nullable', 'integer', Rule::exists('accounts', 'id')->where('team_id', $this->user()->current_team_id)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => __('Choose the statement file to import.'),
            'file.extensions' => __('Use a PDF, CSV or Excel bank statement.'),
            'file.max' => __('The statement must be 10 MB or smaller.'),
            'account_id.exists' => __('That account does not belong to this household.'),
        ];
    }
}
