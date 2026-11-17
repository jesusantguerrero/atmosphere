<?php

use App\Domains\AppCore\Models\Planner;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agenda events belong to a profile through loger_profile_entities, like every other
     * profile link, instead of their own column.
     */
    public function up(): void
    {
        DB::table('planners')->whereNotNull('loger_profile_id')->orderBy('id')
            ->get(['id', 'team_id', 'user_id', 'name', 'loger_profile_id'])
            ->each(fn ($planner) => DB::table('loger_profile_entities')->insert([
                'team_id' => $planner->team_id,
                'user_id' => $planner->user_id,
                'name' => $planner->name ?? '',
                'profile_id' => $planner->loger_profile_id,
                'entity_id' => $planner->id,
                'entity_type' => Planner::class,
                'created_at' => now(),
                'updated_at' => now(),
            ]));

        Schema::table('planners', function (Blueprint $table) {
            $table->dropConstrainedForeignId('loger_profile_id');
        });
    }

    public function down(): void
    {
        Schema::table('planners', function (Blueprint $table) {
            $table->foreignId('loger_profile_id')->nullable()->after('user_id')->constrained('loger_profiles')->nullOnDelete();
        });

        DB::table('loger_profile_entities')->where('entity_type', Planner::class)->orderBy('id')->get()
            ->each(fn ($link) => DB::table('planners')->where('id', $link->entity_id)->update(['loger_profile_id' => $link->profile_id]));
    }
};
