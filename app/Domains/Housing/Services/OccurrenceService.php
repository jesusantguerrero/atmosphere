<?php

namespace App\Domains\Housing\Services;

use App\Domains\Housing\Actions\RegisterOccurrence;
use App\Domains\Housing\Models\Occurrence;
use App\Models\User;

/**
 * Create, edit and delete rhythms (occurrences). A rhythm with a rule is
 * rebuilt from its transactions after every save.
 */
class OccurrenceService
{
    public function __construct(private RegisterOccurrence $registerer) {}

    /**
     * @param  array{name: string, type?: string|null, conditions?: array<string, mixed>|null, is_active?: bool, notify_on_avg?: bool, notify_on_last_count?: bool}  $data
     */
    public function create(User $user, array $data): Occurrence
    {
        $occurrence = Occurrence::create([
            'is_active' => true,
            ...$data,
            'team_id' => $user->current_team_id,
            'user_id' => $user->id,
        ]);
        $this->registerer->sync($occurrence);

        return $occurrence->refresh();
    }

    /**
     * @param  array{name?: string, type?: string|null, conditions?: array<string, mixed>|null, is_active?: bool, notify_on_avg?: bool, notify_on_last_count?: bool}  $data
     */
    public function update(Occurrence $occurrence, array $data): Occurrence
    {
        $occurrence->update($data);
        $this->registerer->sync($occurrence);

        return $occurrence->refresh();
    }

    public function delete(Occurrence $occurrence): void
    {
        $occurrence->delete();
    }
}
