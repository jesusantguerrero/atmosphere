<?php

namespace Database\Seeders;

use App\Domains\AppCore\Models\FeatureFlag;
use Illuminate\Database\Seeder;

/**
 * Seeds the initial catalog of feature flags. Idempotent — uses
 * `updateOrCreate` on `key` so re-running the seeder refreshes metadata
 * without duplicating rows or overwriting the current on/off state
 * (enabled_by_default is only set on first insert).
 */
class FeatureFlagSeeder extends Seeder
{
    public function run(): void
    {
        $flags = [
            [
                'key' => 'trends-relationships',
                'name' => 'Trends · Relationships tab',
                'description' => 'Shows the household relationships breakdown in Trends. '
                    .'Currently backed by mock data; keep off in production until the '
                    .'backend hooks land.',
                'scope' => 'global',
                'category' => 'gating',
                'enabled_by_default' => false,
            ],
            [
                'key' => 'admin-panel',
                'name' => 'Admin panel access',
                'description' => 'Kill-switch for the /admin backoffice. Admins still '
                    .'need role=admin|super_admin; this flag can hide the entry point '
                    .'entirely during incidents or maintenance windows.',
                'scope' => 'global',
                'category' => 'kill_switch',
                'enabled_by_default' => true,
            ],
        ];

        foreach ($flags as $flag) {
            // Only apply enabled_by_default when the row is brand new — never
            // stomp on an admin's deliberate on/off choice by re-running the
            // seeder. Detect "new" by checking existence FIRST, then create /
            // update accordingly, since updateOrCreate's wasRecentlyCreated
            // only survives on the returned model, not a subsequent query.
            $exists = FeatureFlag::query()->where('key', $flag['key'])->exists();

            $attributes = [
                'name' => $flag['name'],
                'description' => $flag['description'],
                'scope' => $flag['scope'],
                'category' => $flag['category'],
            ];

            if (! $exists) {
                $attributes['enabled_by_default'] = $flag['enabled_by_default'];
            }

            FeatureFlag::query()->updateOrCreate(
                ['key' => $flag['key']],
                $attributes,
            );
        }

        $this->command?->info('Seeded '.count($flags).' feature flags.');
    }
}
