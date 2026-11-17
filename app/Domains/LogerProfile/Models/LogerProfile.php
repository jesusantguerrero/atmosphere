<?php

namespace App\Domains\LogerProfile\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Plan\Entities\Plan;
use Modules\Plan\Entities\PlanTypes;

class LogerProfile extends Model
{
    use HasFactory;

    /**
     * Same palette and hash the web avatars use (LogerProfile/Index.vue), so a profile
     * without a chosen color looks the same everywhere.
     */
    const PALETTE = ['#7B77D1', '#F37EA1', '#80CDFE', '#6EE7B7', '#FBBF77', '#A78BFA', '#5EEAD4', '#F59E9E'];

    protected $fillable = [
        'user_id',
        'team_id',
        'name',
        'color',
    ];

    protected function color(): Attribute
    {
        return Attribute::get(fn (?string $value) => $value ?? self::defaultColor((string) ($this->id ?? $this->name ?? '')));
    }

    public static function defaultColor(string $seed): string
    {
        $hash = 0;
        foreach (str_split($seed) as $character) {
            $hash = ($hash * 31 + ord($character)) & 0xFFFFFFFF;
        }

        return self::PALETTE[$hash % count(self::PALETTE)];
    }

    /**
     * The shopping list linked to this profile, which is the one its guests shop from.
     */
    public function shoppingListId(): ?int
    {
        $planIds = LogerProfileEntity::query()->without('entity')
            ->where('team_id', $this->team_id)->where('profile_id', $this->id)
            ->where('entity_type', Plan::class)->pluck('entity_id');

        return Plan::query()->setEagerLoads([])->where('team_id', $this->team_id)->whereIn('id', $planIds)
            ->where('plan_type_name', PlanTypes::SHOPPING_LIST)->orderBy('id')->value('id');
    }
}
