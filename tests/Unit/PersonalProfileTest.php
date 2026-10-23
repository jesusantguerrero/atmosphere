<?php

namespace Tests\Unit;

use App\Domains\LogerProfile\Models\LogerProfile;
use App\Domains\LogerProfile\Services\LogerProfileService;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PersonalProfileTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'personal_profile_test', 'database.connections.personal_profile_test' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]);
        Schema::create('loger_profiles', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('team_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('personal_user_id')->nullable();
            $table->string('name');
            $table->timestamps();
            $table->unique(['team_id', 'personal_user_id']);
        });
    }

    public function test_personal_profile_is_created_once_per_user_and_team_without_claiming_namesakes(): void
    {
        $user = new User;
        $user->forceFill(['id' => 12, 'name' => 'Alex', 'current_team_id' => 30]);
        $namesake = LogerProfile::query()->create(['team_id' => 30, 'user_id' => 12, 'name' => 'Alex']);
        $service = new LogerProfileService;
        $personal = $service->ensurePersonalProfile($user);
        $this->assertNotSame($namesake->id, $personal->id);
        $this->assertSame($personal->id, $service->ensurePersonalProfile($user)->id);
        $this->assertEquals(12, $personal->personal_user_id);
        $this->assertSame('Alex', $personal->name);
        $user->forceFill(['current_team_id' => 31]);
        $this->assertNotSame($personal->id, $service->ensurePersonalProfile($user)->id);
        $user->forceFill(['id' => 13, 'current_team_id' => 30]);
        $this->assertNotSame($personal->id, $service->ensurePersonalProfile($user)->id);
        $this->assertNull($namesake->fresh()->personal_user_id);
        $this->assertSame(4, LogerProfile::query()->count());
    }
}
