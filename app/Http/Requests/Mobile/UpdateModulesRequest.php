<?php

namespace App\Http\Requests\Mobile;

use App\Domains\AppCore\Data\CoreModuleTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateModulesRequest extends FormRequest
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
            'modules' => ['present', 'array'],
            'modules.*' => ['string', 'distinct', Rule::in(array_column(CoreModuleTypeEnum::cases(), 'value'))],
        ];
    }
}
