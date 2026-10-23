<?php

namespace Tests\Unit;

use App\Domains\LogerProfile\Models\LogerProfile;
use App\Domains\LogerProfile\Models\LogerProfileEntity;
use App\Domains\LogerProfile\Services\ProfileWorkspaceService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Plan\Entities\PlanItem;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class TaskProfileLinkTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'task_profiles_test', 'database.connections.task_profiles_test' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]);
        Schema::create('loger_profiles', function (Blueprint $table): void {
            $table->id();
            $table->integer('team_id');
            $table->integer('user_id');
            $table->string('name');
            $table->timestamps();
        });
        Schema::create('loger_profile_entities', function (Blueprint $table): void {
            $table->id();
            $table->integer('team_id');
            $table->integer('user_id');
            $table->integer('profile_id');
            $table->string('name');
            $table->string('entity_type');
            $table->integer('entity_id');
            $table->timestamps();
        });
        Schema::create('plan_items', function (Blueprint $table): void {
            $table->id();
            $table->integer('team_id');
            $table->string('title');
            $table->string('owner');
            $table->boolean('is_done')->default(false);
            $table->timestamps();
        });
    }

    public function test_links_share_one_task_without_changing_owner_or_completion_and_can_be_removed(): void
    {
        $profile = LogerProfile::create(['team_id' => 1, 'user_id' => 3, 'name' => 'Diana']);
        $task = PlanItem::withoutEvents(fn () => PlanItem::unguarded(fn () => PlanItem::create(['team_id' => 1, 'title' => 'Questions', 'owner' => 'Jesus', 'is_done' => true])));
        $service = new ProfileWorkspaceService;
        $service->setTaskProfiles($task, [$profile->id]);
        $service->setTaskProfiles($task, [$profile->id]);
        $this->assertSame(1, LogerProfileEntity::query()->count());
        $this->assertEquals($task->id, LogerProfileEntity::query()->without('entity')->first()->entity_id);
        $stored = PlanItem::query()->without(['fields', 'checklist'])->findOrFail($task->id);
        $this->assertSame('Jesus', $stored->owner);
        $this->assertEquals(true, $stored->is_done);
        $this->assertSame(1, PlanItem::query()->count());
        $service->setTaskProfiles($task, []);
        $this->assertSame(0, LogerProfileEntity::query()->count());
        $this->assertSame(1, PlanItem::query()->count());
    }

    public function test_foreign_profile_cannot_be_linked(): void
    {
        $profile = LogerProfile::create(['team_id' => 2, 'user_id' => 3, 'name' => 'Foreign']);
        $task = PlanItem::withoutEvents(fn () => PlanItem::unguarded(fn () => PlanItem::create(['team_id' => 1, 'title' => 'Task', 'owner' => 'Jesus'])));
        try {
            (new ProfileWorkspaceService)->setTaskProfiles($task, [$profile->id]);
            $this->fail('Foreign link accepted');
        } catch (HttpException $exception) {
            $this->assertSame(422, $exception->getStatusCode());
        }
        $this->assertSame(0, LogerProfileEntity::query()->count());
    }
}
