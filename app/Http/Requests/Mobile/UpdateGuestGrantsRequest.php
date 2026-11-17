<?php

namespace App\Http\Requests\Mobile;

use App\Models\TeamMemberGrant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGuestGrantsRequest extends FormRequest
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
        return self::grantRules($this->user()->current_team_id, required: true);
    }

    /**
     * Profiles of the household and the access to each; shared with guest invitations.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function grantRules(?int $teamId, bool $required): array
    {
        return [
            'grants' => [$required ? 'present' : 'nullable', 'array'],
            'grants.*.profile_id' => ['required', 'integer', Rule::exists('loger_profiles', 'id')->where('team_id', $teamId)],
            'grants.*.access' => ['required', Rule::in([TeamMemberGrant::ACCESS_READ, TeamMemberGrant::ACCESS_WRITE])],
        ];
    }
}
