<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Backfill the `super_admin` role on the user identified by the
 * APP_SUPER_ADMIN env var so the historic env-based check stays authoritative
 * after the role column is introduced. Idempotent — safe to run multiple
 * times; no-op if the env var is unset or the matching user doesn't exist.
 */
class RoleBackfillSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('atmosphere.superadmin.email');

        if (empty($email)) {
            $this->command?->warn('APP_SUPER_ADMIN not set — no super_admin backfill performed.');

            return;
        }

        $updated = User::query()
            ->where('email', $email)
            ->update(['role' => 'super_admin']);

        if ($updated) {
            $this->command?->info("Super admin role assigned to {$email}.");
        } else {
            $this->command?->warn("No user found for APP_SUPER_ADMIN ({$email}). Role backfill skipped.");
        }
    }
}
