<?php

namespace App\Domains\LogerProfile\Services;

use App\Domains\AppCore\Models\Planner;
use App\Domains\LogerProfile\Models\LogerProfile;
use App\Domains\LogerProfile\Models\LogerProfileEntity;
use App\Models\Setting;
use App\Models\Team;
use App\Models\TeamMemberGrant;
use App\Models\User;
use App\Notifications\GuestEventChanged;
use App\Notifications\GuestShoppingItemsAdded;
use App\Notifications\LogerNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Modules\Plan\Entities\Plan;
use Modules\Plan\Entities\PlanItem;
use Modules\Plan\Entities\PlanTypes;
use Throwable;

/**
 * What a guest hears about: only what is linked to the profiles she was granted.
 * Agenda changes of the next week, the Sunday summary and new items on her lists.
 */
class GuestAlertService
{
    public const EVENT_WINDOW_DAYS = 7;

    public const DIGEST_DAYS = 30;

    public const SHOPPING_WINDOW_MINUTES = 10;

    /** Several writes of one edit (link + update) land as a single alert. */
    private const EVENT_ALERT_SECONDS = 120;

    private const WATCHED_FIELDS = ['name', 'date', 'end_date', 'notes', 'status'];

    public function eventLinked(LogerProfileEntity $link): void
    {
        $planner = Planner::query()->where('team_id', $link->team_id)->find($link->entity_id);
        if ($planner && $planner->status !== Planner::STATUS_CANCELLED && $planner->status !== Planner::STATUS_COMPLETED) {
            $this->alertEvent($planner, GuestEventChanged::ADDED, [(int) $link->profile_id]);
        }
    }

    public function plannerUpdated(Planner $planner): void
    {
        $changed = array_intersect(array_keys($planner->getChanges()), self::WATCHED_FIELDS);
        if (! $changed || $planner->status === Planner::STATUS_COMPLETED) {
            return;
        }

        $change = $planner->status === Planner::STATUS_CANCELLED ? GuestEventChanged::CANCELLED : GuestEventChanged::CHANGED;
        $this->alertEvent($planner, $change, $this->eventProfileIds($planner), $planner->getOriginal('date'));
    }

    public function plannerDeleting(Planner $planner): void
    {
        if ($planner->status !== Planner::STATUS_COMPLETED && $planner->status !== Planner::STATUS_CANCELLED) {
            $this->alertEvent($planner, GuestEventChanged::REMOVED, $this->eventProfileIds($planner));
        }
    }

    /**
     * The agenda of her profiles: the coming week and what follows within the month.
     *
     * @return array{profiles: string, week: list<array{date: string, name: string, profile: string|null}>, later: list<array{date: string, name: string, profile: string|null}>}|null
     */
    public function weeklyDigestFor(Team $team, User $guest, Carbon $today): ?array
    {
        $profileIds = $guest->grantedProfileIds($team);
        if (! $profileIds) {
            return null;
        }

        $workspace = app(ProfileWorkspaceService::class);
        $weekEnd = $today->copy()->addDays(self::EVENT_WINDOW_DAYS)->format('Y-m-d');
        $planners = Planner::query()->where('team_id', $team->id)
            ->whereIn('id', $workspace->eventIdsFor($team->id, $profileIds))
            ->whereBetween('date', [$today->copy()->addDay()->format('Y-m-d'), $today->copy()->addDays(self::DIGEST_DAYS)->format('Y-m-d')])
            ->whereNotIn('status', [Planner::STATUS_COMPLETED, Planner::STATUS_CANCELLED])
            ->orderBy('date')->get();

        if ($planners->isEmpty()) {
            return null;
        }

        $profiles = LogerProfile::query()->where('team_id', $team->id)->whereIn('id', $profileIds)->orderBy('name')->pluck('name');
        $eventProfiles = $workspace->eventProfiles($team->id, $planners->modelKeys());
        $events = $planners->map(fn (Planner $planner) => [
            'date' => $planner->date->format('Y-m-d'),
            'name' => $planner->name,
            'profile' => $profiles->count() > 1 ? $eventProfiles->get($planner->id)?->name : null,
        ]);

        return [
            'profiles' => $profiles->join(', ', ' '.__('and').' '),
            'week' => $events->filter(fn (array $event) => $event['date'] <= $weekEnd)->values()->all(),
            'later' => $events->filter(fn (array $event) => $event['date'] > $weekEnd)->values()->all(),
        ];
    }

    /**
     * One alert per guest and list with what others added since the last look.
     */
    public function sendShoppingDigests(Carbon $now): void
    {
        TeamMemberGrant::query()->with('user')->get()->groupBy(fn (TeamMemberGrant $grant) => $grant->team_id.':'.$grant->user_id)
            ->each(function (Collection $grants) use ($now) {
                $guest = $grants->first()->user;
                $teamId = (int) $grants->first()->team_id;
                if (! $guest) {
                    return;
                }

                $seenKey = 'guest-shopping-seen:'.$teamId.':'.$guest->id;
                $since = Carbon::parse(Cache::get($seenKey, $now->copy()->subMinutes(self::SHOPPING_WINDOW_MINUTES)));
                Cache::forever($seenKey, $now->toIso8601String());

                $items = PlanItem::query()->setEagerLoads([])
                    ->whereIn('plan_id', $this->shoppingListIds($teamId, $grants->pluck('loger_profile_id')->all()))
                    ->where('created_at', '>', $since)->where('created_at', '<=', $now)
                    ->where(fn ($query) => $query->whereNull('user_id')->orWhere('user_id', '!=', $guest->id))
                    ->orderBy('id')->get(['id', 'plan_id', 'title']);

                $items->groupBy('plan_id')->each(function (Collection $planItems, $planId) use ($guest) {
                    $listName = Plan::query()->setEagerLoads([])->whereKey($planId)->value('name') ?? '';
                    $this->send($guest, new GuestShoppingItemsAdded((int) $planId, $listName, $planItems->pluck('title')->all()));
                });
            });
    }

    public function send(User $guest, LogerNotification $notification): void
    {
        try {
            $guest->notify($notification->locale($guest->language ?: app()->getLocale()));
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    /**
     * @param  array<int, int>  $profileIds
     */
    private function alertEvent(Planner $planner, string $change, array $profileIds, mixed $previousDate = null): void
    {
        if (! $profileIds || ! $this->withinWindow($planner, $previousDate)) {
            return;
        }

        $profileNames = LogerProfile::query()->where('team_id', $planner->team_id)->whereIn('id', $profileIds)->pluck('name', 'id');

        TeamMemberGrant::query()->where('team_id', $planner->team_id)->whereIn('loger_profile_id', $profileIds)
            ->where('user_id', '!=', (int) auth()->id())->with('user')->get()->groupBy('user_id')
            ->each(function (Collection $grants) use ($planner, $change, $profileNames) {
                $guest = $grants->first()->user;
                if (! $guest || ! Cache::add('guest-event-alert:'.$guest->id.':'.$planner->id, true, self::EVENT_ALERT_SECONDS)) {
                    return;
                }

                $this->send($guest, new GuestEventChanged(
                    $change,
                    (int) $planner->id,
                    (string) $planner->name,
                    $planner->date->format('Y-m-d'),
                    (string) $profileNames->get($grants->first()->loger_profile_id, ''),
                ));
            });
    }

    private function withinWindow(Planner $planner, mixed $previousDate): bool
    {
        $timeZone = Setting::getByTeam($planner->team_id)['team_timezone'] ?? config('app.timezone');
        $today = Carbon::now($timeZone)->format('Y-m-d');
        $until = Carbon::now($timeZone)->addDays(self::EVENT_WINDOW_DAYS)->format('Y-m-d');

        return collect([$planner->date, $previousDate])->filter()
            ->map(fn ($date) => Carbon::parse($date)->format('Y-m-d'))
            ->contains(fn (string $date) => $date >= $today && $date <= $until);
    }

    /**
     * @return array<int, int>
     */
    private function eventProfileIds(Planner $planner): array
    {
        return LogerProfileEntity::query()->without('entity')->where('team_id', $planner->team_id)
            ->where('entity_type', Planner::class)->where('entity_id', $planner->id)
            ->pluck('profile_id')->map(fn ($id) => (int) $id)->all();
    }

    /**
     * @param  array<int, int>  $profileIds
     * @return array<int, int>
     */
    private function shoppingListIds(int $teamId, array $profileIds): array
    {
        $planIds = LogerProfileEntity::query()->without('entity')->where('team_id', $teamId)
            ->where('entity_type', Plan::class)->whereIn('profile_id', $profileIds)->pluck('entity_id');

        return Plan::query()->setEagerLoads([])->where('team_id', $teamId)
            ->where('plan_type_name', PlanTypes::SHOPPING_LIST)->whereIn('id', $planIds)
            ->pluck('id')->map(fn ($id) => (int) $id)->all();
    }
}
