<?php

namespace App\Domains\LogerProfile\Services;

use App\Domains\AppCore\Models\Category;
use App\Domains\Budget\Models\BudgetMonth;
use App\Domains\Housing\Models\Occurrence;
use App\Domains\LogerProfile\Models\LogerProfile;
use App\Domains\LogerProfile\Models\LogerProfileEntity;
use App\Domains\Transaction\Models\Transaction;
use App\Domains\Transaction\Models\TransactionLine;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Modules\Plan\Entities\FieldValue;
use Modules\Plan\Entities\Plan;
use Modules\Plan\Entities\PlanItem;
use Modules\Plan\Entities\PlanStage;
use Modules\Plan\Entities\PlanTypes;

class ProfileWorkspaceService
{
    public const TYPES = ['category' => Category::class, 'plan' => Plan::class, 'occurrence' => Occurrence::class, 'task' => PlanItem::class];

    public const VIEWS = ['notebook' => 1, 'kanban' => 2, 'list' => 3];

    public function overview(int $teamId): array
    {
        $profiles = LogerProfile::query()->where('team_id', $teamId)->orderByRaw('CASE WHEN personal_user_id = ? THEN 0 ELSE 1 END', [auth()->id() ?? 0])->orderBy('name')->get();
        $links = LogerProfileEntity::query()->without('entity')->where('team_id', $teamId)->whereIn('profile_id', $profiles->modelKeys())->get();
        $planIds = $links->where('entity_type', Plan::class)->pluck('entity_id');
        $itemIds = $links->where('entity_type', PlanItem::class)->pluck('entity_id');
        $plans = Plan::query()->setEagerLoads([])->where('team_id', $teamId)->whereIn('id', $planIds)->get()->keyBy('id');
        $items = PlanItem::query()->without('checklist')->where('team_id', $teamId)
            ->where(fn ($query) => $query->whereIn('plan_id', $planIds)->orWhereIn('id', $itemIds))->get();

        return $profiles->map(function ($profile) use ($links, $plans, $items) {
            $profileLinks = $links->where('profile_id', $profile->id);
            $linkedPlans = $profileLinks->where('entity_type', Plan::class)->pluck('entity_id');
            $linkedItems = $profileLinks->where('entity_type', PlanItem::class)->pluck('entity_id');
            $pages = $items->filter(fn ($item) => $linkedPlans->contains($item->plan_id) || $linkedItems->contains($item->id))
                ->reject(fn ($item) => $plans->get($item->plan_id)?->plan_type_name === PlanTypes::SHOPPING_LIST)
                ->map(fn ($item) => $this->page($item, $linkedItems->contains($item->id) || $plans->get($item->plan_id)?->plan_type_name === PlanTypes::CHORES));

            return [
                'id' => $profile->id, 'name' => $profile->name, 'personal_user_id' => $profile->personal_user_id,
                'url' => route('loger-profiles.show', ['profileId' => $profile->id], false),
                'pending' => $pages->where('kind', 'task')->where('done', false)->values()->all(),
                'upcoming' => $pages->where('kind', 'event')->where('done', false)->filter(fn ($page) => $page['when'] && Carbon::parse($page['when'])->greaterThanOrEqualTo(today()))->sortBy('when')->values()->all(),
            ];
        })->all();
    }

    public function setTaskProfiles(PlanItem $task, array $profileIds): void
    {
        $task->getConnection()->transaction(function () use ($task, $profileIds): void {
            $profiles = LogerProfile::query()->where('team_id', $task->team_id)->whereIn('id', $profileIds)->get();
            abort_unless($profiles->count() === count(array_unique($profileIds)), 422);
            PlanItem::query()->without(['fields', 'checklist'])->whereKey($task->id)->lockForUpdate()->firstOrFail();
            LogerProfileEntity::query()->without('entity')->where('team_id', $task->team_id)->where('entity_type', PlanItem::class)->where('entity_id', $task->id)->whereNotIn('profile_id', $profileIds)->delete();
            foreach ($profiles as $profile) {
                $this->link($profile, $task, 'task');
            }
        });
    }

    public function links(LogerProfile $profile): Collection
    {
        return LogerProfileEntity::query()->without('entity')->where('team_id', $profile->team_id)->where('profile_id', $profile->id)->get();
    }

    public function link(LogerProfile $profile, Model $entity, string $type): void
    {
        abort_unless((int) $entity->team_id === (int) $profile->team_id, 404);
        LogerProfileEntity::query()->without('entity')->firstOrCreate([
            'team_id' => $profile->team_id, 'profile_id' => $profile->id,
            'entity_type' => self::TYPES[$type], 'entity_id' => $entity->id,
        ], ['user_id' => $profile->user_id, 'name' => $entity->name ?? $entity->title]);
    }

    public function linked(LogerProfile $profile, string $type, int $id): Model
    {
        $model = self::TYPES[$type]::query()->where('team_id', $profile->team_id)->findOrFail($id);
        abort_unless($this->links($profile)->contains(fn ($link) => $link->entity_type === self::TYPES[$type] && (int) $link->entity_id === $id), 404);

        return $model;
    }

    public function item(LogerProfile $profile, int $id): PlanItem
    {
        $item = PlanItem::query()->where('team_id', $profile->team_id)->findOrFail($id);
        $linked = $this->links($profile);
        abort_unless($linked->contains(fn ($link) => ($link->entity_type === Plan::class && (int) $link->entity_id === (int) $item->plan_id)
            || ($link->entity_type === PlanItem::class && (int) $link->entity_id === $id)), 404);

        return $item;
    }

    public function saveItemFields(Plan $plan, PlanItem $item, array $data): void
    {
        foreach (['body' => ['description', 'Contenido', 'text'], 'kind' => ['page_kind', 'Tipo de página', 'text'], 'when' => ['date', 'Fecha', 'date']] as $input => [$name, $title, $type]) {
            if (! array_key_exists($input, $data)) {
                continue;
            }
            $field = $plan->findOrCreateField(['name' => $name, 'title' => $title, 'type' => $type]);
            $identity = ['field_id' => $field->id, 'entity_id' => $item->id, 'entity_type' => PlanItem::class];
            $value = FieldValue::query()->where($identity)->first() ?? (new FieldValue)->forceFill($identity);
            $value->forceFill([
                'team_id' => $item->team_id, 'user_id' => $item->user_id, 'resource' => 'item',
                'field_name' => $name, 'field_title' => $title, 'value' => $data[$input] ?? '',
                'date_value' => $input === 'when' && ! empty($data[$input]) ? Carbon::parse($data[$input])->toDateString() : null,
                'time_value' => $input === 'when' && ! empty($data[$input]) ? Carbon::parse($data[$input])->format('H:i:s') : null,
            ])->save();
        }
    }

    public function createBoard(LogerProfile $profile, array $data, int $userId): Plan
    {
        return $profile->getConnection()->transaction(function () use ($profile, $data, $userId) {
            $plan = (new Plan)->forceFill([
                'team_id' => $profile->team_id, 'user_id' => $userId, 'name' => $data['name'],
                'plan_type_name' => PlanTypes::CUSTOM, 'description' => $data['description'] ?? '',
                'color' => $data['color'] ?? '#9b8be8', 'last_view_id' => self::VIEWS[$data['view']],
            ]);
            $plan->save();
            $this->createStage($profile, $plan, 'Primer cuaderno', $userId);
            $this->link($profile, $plan, 'plan');

            return $plan;
        });
    }

    public function createStage(LogerProfile $profile, Plan $plan, string $name, int $userId): PlanStage
    {
        return $plan->stages()->create(['team_id' => $profile->team_id, 'user_id' => $userId, 'name' => $name, 'order' => $plan->stages()->max('order') + 1]);
    }

    public function createPage(LogerProfile $profile, Plan $plan, PlanStage $stage, array $data, int $userId): PlanItem
    {
        abort_unless((int) $stage->plan_id === $plan->id && (int) $stage->team_id === (int) $profile->team_id, 404);

        return $profile->getConnection()->transaction(function () use ($profile, $plan, $stage, $data, $userId) {
            $item = PlanItem::create([
                'team_id' => $profile->team_id, 'user_id' => $userId, 'plan_id' => $plan->id,
                'stage_id' => $stage->id, 'title' => $data['title'], 'is_done' => $data['done'] ?? false,
                'order' => $stage->items()->max('order') + 1,
            ]);
            $item->forceFill(['profile_id' => $profile->id, 'rrule' => null])->saveQuietly();
            $this->saveItemFields($plan, $item, ['body' => $data['body'] ?? '', 'kind' => $data['kind'] ?? 'note', 'when' => $data['when'] ?? null]);

            return $item;
        });
    }

    public function saveLinks(LogerProfile $profile, array $selections): void
    {
        $profile->getConnection()->transaction(function () use ($profile, $selections) {
            $entities = [];
            foreach ($selections as $selection) {
                $type = $selection['type'];
                $entity = self::TYPES[$type]::query()->where('team_id', $profile->team_id)->findOrFail($selection['id']);
                $entities[] = [$type, $entity];
            }
            $keys = collect($entities)->map(fn ($entry) => self::TYPES[$entry[0]].':'.$entry[1]->id);
            foreach ($this->links($profile)->whereIn('entity_type', array_values(self::TYPES)) as $link) {
                if (! $keys->contains($link->entity_type.':'.$link->entity_id)) {
                    $link->delete();
                }
            }
            foreach ($entities as [$type, $entity]) {
                $this->link($profile, $entity, $type);
            }
        });
    }

    public function registerToday(LogerProfile $profile, int $id, bool $checked): void
    {
        $profile->getConnection()->transaction(function () use ($profile, $id, $checked) {
            $this->linked($profile, 'occurrence', $id);
            $occurrence = Occurrence::query()->lockForUpdate()->findOrFail($id);
            $today = today()->toDateString();
            $log = collect($occurrence->log ?? [])->map(fn ($date) => Carbon::parse($date)->toDateString());
            if ($occurrence->last_date && ! $log->contains($occurrence->last_date->toDateString())) {
                $log->push($occurrence->last_date->toDateString());
            }
            if ($checked && ! $log->contains($today)) {
                $log->push($today);
            } elseif (! $checked) {
                $log = $log->reject(fn ($date) => $date === $today)->values();
            }
            $log = $log->sort()->values();
            $count = $log->count();
            $total = $count > 1 ? (int) Carbon::parse($log->first())->diffInDays(Carbon::parse($log->last())) : 0;
            $previous = $count > 1 ? (int) Carbon::parse($log[$count - 2])->diffInDays(Carbon::parse($log->last())) : 0;
            $occurrence->update(['log' => $log->all(), 'last_date' => $log->last(), 'occurrence_count' => $count, 'total_days' => $total, 'previous_days_count' => $previous, 'avg_days_passed' => $count > 0 ? $total / $count : 0]);
        });
    }

    private function page(PlanItem $item, bool $task = false): array
    {
        $values = $item->fields->where('team_id', $item->team_id)->where('resource', 'item')->where('entity_type', PlanItem::class)->keyBy('field_name');

        return [
            'id' => (string) $item->id, 'title' => $item->title, 'done' => (bool) $item->is_done,
            'body' => $values->get('description')?->value ?? '',
            'kind' => $values->get('page_kind')?->value ?? ($task ? 'task' : 'note'),
            'when' => $values->get('date')?->value ?? null,
            'source_url' => route('plans.show', ['plan' => $item->plan_id], false).'?task='.$item->id,
        ];
    }

    public function snapshot(LogerProfile $profile): array
    {
        $links = $this->links($profile);
        $ids = fn (string $class) => $links->where('entity_type', $class)->pluck('entity_id');
        $plans = Plan::query()->where('team_id', $profile->team_id)->whereIn('id', $ids(Plan::class))
            ->with(['stages.items.fields'])->orderBy('id')->get();
        $boards = $plans->reject(fn ($plan) => $plan->plan_type_name === PlanTypes::SHOPPING_LIST)->map(fn ($plan) => [
            'id' => (string) $plan->id, 'name' => $plan->name, 'description' => $plan->description ?? '', 'color' => $plan->color ?? '#9b8be8',
            'view' => array_search((int) $plan->last_view_id, self::VIEWS, true) ?: 'notebook',
            'stages' => $plan->stages->map(fn ($stage) => ['id' => (string) $stage->id, 'name' => $stage->name,
                'items' => $stage->items->where('team_id', $profile->team_id)->sortBy('order')->map(fn ($item) => $this->page($item, $plan->plan_type_name === PlanTypes::CHORES))->values(),
            ])->values(),
        ])->values();
        $purchases = $plans->filter(fn ($plan) => $plan->plan_type_name === PlanTypes::SHOPPING_LIST)->flatMap(fn ($plan) => $plan->stages->flatMap(fn ($stage) => $stage->items->where('team_id', $profile->team_id)->map(fn ($item) => ['id' => $item->id, 'name' => $item->title, 'detail' => $this->page($item)['body'], 'done' => (bool) $item->is_done])))->values();
        $occurrences = Occurrence::query()->where('team_id', $profile->team_id)->whereIn('id', $ids(Occurrence::class))->get()->map(fn ($item) => [
            'id' => $item->id, 'name' => $item->name, 'detail' => $item->last_date ? 'Último registro: '.$item->last_date->format('d/m/Y') : 'Sin registros todavía',
            'checked' => collect($item->log ?? [])->contains(fn ($date) => Carbon::parse($date)->isToday()),
        ]);
        $allCategories = Category::query()->where('team_id', $profile->team_id)->where('resource_type', 'transactions')->get();
        $categoryIds = $ids(Category::class)->map(fn ($id) => (int) $id)->all();
        do {
            $before = count($categoryIds);
            $categoryIds = array_values(array_unique([...$categoryIds, ...$allCategories->whereIn('parent_id', $categoryIds)->pluck('id')->all()]));
        } while (count($categoryIds) !== $before);
        $categories = $allCategories->whereIn('id', $categoryIds)->filter(fn ($item) => $item->parent_id !== null);
        $months = BudgetMonth::query()->where('team_id', $profile->team_id)->where('month', now()->startOfMonth()->toDateString())->whereIn('category_id', $categoryIds)->get()->keyBy('category_id');
        $transactions = TransactionLine::query()->setEagerLoads([])->join('transactions', 'transactions.id', '=', 'transaction_lines.transaction_id')
            ->where('transaction_lines.team_id', $profile->team_id)->where('transactions.team_id', $profile->team_id)
            ->whereIn('transaction_lines.category_id', $categoryIds)->where('transactions.status', Transaction::STATUS_VERIFIED)->whereNull('transactions.deleted_at')
            ->orderByDesc('transaction_lines.date')->orderByDesc('transaction_lines.id')->limit(20)
            ->get(['transaction_lines.id', 'transaction_lines.category_id', 'transaction_lines.amount', 'transaction_lines.type', 'transactions.description', 'transactions.date', 'transactions.currency_code'])
            ->map(fn ($line) => ['id' => $line->id, 'name' => $line->getRawOriginal('description'), 'date' => $line->date, 'amount' => (float) $line->amount * (int) $line->type, 'currency' => $line->currency_code, 'category' => $allCategories->find($line->category_id)?->name]);
        $options = [];
        foreach (self::TYPES as $type => $class) {
            $query = $class::query()->setEagerLoads([])->where('team_id', $profile->team_id);
            if ($type === 'category') {
                $query->where('resource_type', 'transactions')->whereNotIn('display_id', ['ready_to_assign', 'inflow', 'credit_card_payments']);
            }
            foreach ($query->orderBy('id')->get() as $entity) {
                $options[] = ['key' => $type.':'.$entity->id, 'type' => $type, 'id' => $entity->id, 'name' => $entity->name ?? $entity->title,
                    'label' => $type === 'category' && ! $entity->parent_id ? 'Grupo de categorías' : ['category' => 'Categoría', 'plan' => 'Tablero o lista', 'occurrence' => 'Seguimiento', 'task' => 'Elemento de Plan'][$type],
                    'linked' => $links->contains(fn ($link) => $link->entity_type === $class && (int) $link->entity_id === (int) $entity->id),
                ];
            }
        }

        return [
            'boards' => $boards, 'purchases' => $purchases, 'occurrences' => $occurrences->values(),
            'categories' => $categories->map(fn ($category) => ['id' => $category->id, 'name' => $category->name, 'assigned' => (float) ($months->get($category->id)?->budgeted ?? 0), 'available' => (float) ($months->get($category->id)?->available ?? 0)])->values(),
            'categoryGroup' => $allCategories->whereIn('id', $ids(Category::class))->pluck('name')->implode(', '),
            'transactions' => $transactions, 'month' => now()->format('Y-m'),
            'connections' => ['finance' => count($categoryIds) > 0, 'shopping' => $plans->contains(fn ($plan) => $plan->plan_type_name === PlanTypes::SHOPPING_LIST), 'tracking' => $occurrences->isNotEmpty()],
            'availableLinks' => $options,
            'tasks' => PlanItem::query()->where('team_id', $profile->team_id)->whereIn('id', $ids(PlanItem::class))->whereNotIn('plan_id', $ids(Plan::class))->get()->map(fn ($item) => $this->page($item, true))->values(),
        ];
    }
}
