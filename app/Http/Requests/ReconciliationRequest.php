<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Insane\Journal\Models\Accounting\Reconciliation;
use Insane\Journal\Models\Core\Account;

class ReconciliationRequest extends FormRequest
{
    /**
     * Authorize before validating, so another household's reconciliation or
     * account answers 403 instead of a validation redirect that reveals it exists.
     */
    public function authorize(): bool
    {
        $reconciliation = $this->route('reconciliation');
        if ($reconciliation instanceof Reconciliation) {
            return $this->user()->can('adjust', $reconciliation);
        }

        $account = $this->route('account');

        return ! $account instanceof Account || $this->user()->can('update', $account);
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
