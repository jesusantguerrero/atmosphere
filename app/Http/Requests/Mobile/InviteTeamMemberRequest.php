<?php

namespace App\Http\Requests\Mobile;

use App\Models\TeamMemberGrant;
use Illuminate\Foundation\Http\FormRequest;

class InviteTeamMemberRequest extends FormRequest
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
            'email' => ['required', 'email', 'max:255'],
            'role' => ['required', 'string'],
            ...UpdateGuestGrantsRequest::grantRules($this->user()->current_team_id, required: false),
            'grants' => ['nullable', 'array', 'prohibited_unless:role,'.TeamMemberGrant::GUEST_ROLE],
        ];
    }
}
