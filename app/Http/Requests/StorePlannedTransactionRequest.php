<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class StorePlannedTransactionRequest extends FormRequest
{
    /** The values of the `planners.frequency` enum. */
    public const FREQUENCIES = ['DAILY', 'WEEK_DAY', 'WEEK', 'MONTHLY'];

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * The form sends the frequency in any case (WEEKLY for the WEEK enum value) and the
     * end date as a picker timestamp (ms).
     */
    protected function prepareForValidation(): void
    {
        $endDate = $this->input('end_date');
        if ($this->input('end_type') !== 'DATE') {
            $endDate = null;
        } elseif (is_numeric($endDate)) {
            $endDate = Carbon::createFromTimestampMs((int) $endDate)->format('Y-m-d');
        }

        $frequency = $this->filled('frequency') ? strtoupper((string) $this->input('frequency')) : 'MONTHLY';

        $this->merge([
            'frequency' => $frequency === 'WEEKLY' ? 'WEEK' : $frequency,
            'end_date' => $endDate,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'date' => ['required', 'date'],
            'frequency' => ['nullable', 'string', 'in:'.implode(',', self::FREQUENCIES)],
            'frequency_interval' => ['nullable', 'integer', 'min:1', 'max:52'],
            'end_date' => ['nullable', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'frequency.in' => 'Pick how often it repeats: daily, weekdays, weekly or monthly.',
            'frequency_interval.integer' => 'The repeat interval must be a whole number.',
            'frequency_interval.min' => 'It has to repeat at least every 1 week or month.',
            'frequency_interval.max' => 'It can repeat at most every 52 weeks or months.',
        ];
    }
}
