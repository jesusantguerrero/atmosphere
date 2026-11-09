<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Plan\Entities\PlanTypes;
use Modules\Plan\Services\PlanService;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class SharedShoppingRealtimeTest extends TestCase
{
    use RefreshDatabase;

    public function test_shared_list_exposes_the_public_hub_only_when_broadcasting_uses_mercure(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $plan = app(PlanService::class)->createPlanBoard($user->currentTeam, PlanTypes::SHOPPING_LIST, 'Compra');
        $plan->forceFill(['share_token' => (string) Str::uuid()])->save();
        $page = '/shared/list/'.$plan->share_token;

        config([
            'broadcasting.default' => 'mercure',
            'broadcasting.connections.mercure.url' => 'http://mercure/.well-known/mercure',
            'broadcasting.connections.mercure.hub_url' => 'https://mercure.example.com/.well-known/mercure',
        ]);
        $this->get($page)->assertInertia(fn (Assert $inertia) => $inertia
            ->component('ShoppingList/SharedShow')
            ->where('mercureUrl', 'https://mercure.example.com/.well-known/mercure?topic='.urlencode(url($page))));

        config(['broadcasting.default' => 'null']);
        $this->get($page)->assertInertia(fn (Assert $inertia) => $inertia->where('mercureUrl', null));
    }

    public function test_client_reads_the_payload_inside_the_broadcaster_envelope(): void
    {
        // Message as received from the hub (captured from dunglas/mercure v0.18).
        $script = <<<'JS'
const fs = require('node:fs');
const vm = require('node:vm');
const assert = require('node:assert/strict');
const ts = require('typescript');
const scope = {exports: {}};
vm.createContext(scope);
vm.runInContext(ts.transpileModule(fs.readFileSync('resources/js/domains/shopping/mercure.ts', 'utf8'), {compilerOptions: {module: ts.ModuleKind.CommonJS}}).outputText, scope);
const unwrap = scope.exports.unwrapMercureMessage;
const wrapped = '{"event":"App\\\\Events\\\\ShoppingListItemUpdated","data":{"action":"updated","review":{"reviewed_ids":[3],"reviewed_revision":7}}}';
assert.equal(unwrap(wrapped).action, 'updated');
assert.equal(unwrap(wrapped).review.reviewed_revision, 7);
assert.equal(unwrap('{"action":"reset"}').action, 'reset');
assert.equal(unwrap('null'), null);
JS;

        $process = new Process(['node', '-e', $script], base_path());
        $process->run();
        $this->assertTrue($process->isSuccessful(), $process->getErrorOutput().$process->getOutput());
    }
}
