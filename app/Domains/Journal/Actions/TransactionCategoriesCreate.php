<?php

namespace App\Domains\Journal\Actions;

use Insane\Journal\Contracts\TransactionCategoriesCreates;
use Insane\Journal\Models\Core\Category;

class TransactionCategoriesCreate implements TransactionCategoriesCreates
{
    /**
     * display_ids whose `name` MUST stay as the literal English string from
     * config/journal.php — they're matched by `.name` in several code paths
     * (BudgetReservedNames::INFLOW, ::READY_TO_ASSIGN). Everything else can
     * be translated freely.
     */
    private const RESERVED_DISPLAY_IDS = [
        'inflow',
        'ready_to_assign',
    ];

    public function create($team)
    {
        $generalInfo = [
            'team_id' => $team->id,
            'user_id' => $team->user_id,
            'depth' => 0,
        ];

        Category::where([
            'team_id' => $team->id,
            'resource_type' => 'transactions',
        ])->delete();

        $categories = $this->localize(config('journal.categories'));
        Category::saveBulk($categories, $generalInfo);
    }

    /**
     * Recursively walk the config tree and swap `name` / `description` with
     * their translations from resources/lang/{locale}/categories.php when a
     * key matching the row's `display_id` exists. Falls back to the config's
     * English value when no translation is defined for the current locale.
     *
     * This runs at seed-time using the request's active locale — so a user
     * hitting /register on the Spanish landing page gets Spanish category
     * names, and an English signup gets English. It does NOT retro-translate
     * a team's existing categories if the owner later switches language.
     */
    private function localize(array $categories): array
    {
        return array_map(function (array $row) {
            $displayId = $row['display_id'] ?? null;

            if ($displayId && ! in_array($displayId, self::RESERVED_DISPLAY_IDS, true)) {
                $translated = trans('categories.'.$displayId);
                if ($translated !== 'categories.'.$displayId) {
                    $row['name'] = $translated;
                }

                $descKey = 'categories.'.$displayId.'_desc';
                $translatedDesc = trans($descKey);
                if ($translatedDesc !== $descKey) {
                    $row['description'] = $translatedDesc;
                }
            }

            if (! empty($row['childs']) && is_array($row['childs'])) {
                $row['childs'] = $this->localize($row['childs']);
            }

            return $row;
        }, $categories);
    }
}
