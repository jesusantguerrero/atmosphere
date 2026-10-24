<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileWorkspaceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return match ($this->route()->getActionMethod()) {
            'createBoard' => ['name' => ['required', 'string', 'max:150'], 'view' => ['required', Rule::in(['notebook', 'kanban', 'list'])]],
            'updateBoard' => ['view' => ['required', Rule::in(['notebook', 'kanban', 'list'])]],
            'createNotebook' => ['name' => ['required', 'string', 'max:150']],
            'updateNotebook' => ['name' => ['required', 'string', 'max:150']],
            'createPage' => ['stage_id' => ['required', 'integer'], 'title' => ['nullable', 'string', 'max:255']],
            'updateItem' => ['title' => ['sometimes', 'required', 'string', 'max:255'], 'body' => ['sometimes', 'nullable', 'string', 'max:50000'], 'kind' => ['sometimes', Rule::in(['note', 'task', 'event'])], 'when' => ['sometimes', 'nullable', 'date'], 'done' => ['sometimes', 'boolean']],
            'saveLinks' => ['selections' => ['present', 'array'], 'selections.*.type' => ['required', Rule::in(['category', 'plan', 'occurrence', 'task'])], 'selections.*.id' => ['required', 'integer']],
            'registerOccurrence' => ['checked' => ['required', 'boolean']],
            default => [],
        };
    }

    public function messages(): array
    {
        return ['required' => 'Este campo es obligatorio.', 'max' => 'El contenido es demasiado largo.', 'date' => 'Introduce una fecha válida.', 'in' => 'La opción seleccionada no es válida.'];
    }
}
