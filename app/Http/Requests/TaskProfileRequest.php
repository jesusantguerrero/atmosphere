<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TaskProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return ['profile_ids' => ['present', 'array', 'max:100'], 'profile_ids.*' => ['integer', 'distinct', Rule::exists('loger_profiles', 'id')->where('team_id', $this->user()->current_team_id)]];
    }

    public function messages(): array
    {
        return ['profile_ids.present' => 'Selecciona los perfiles relacionados.', 'profile_ids.*.exists' => 'El perfil no pertenece a este equipo.', 'profile_ids.max' => 'Selecciona hasta 100 perfiles.'];
    }
}
