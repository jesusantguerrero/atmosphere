<?php

namespace Tests\Feature;

use App\Domains\Budget\Models\BudgetTarget;
use App\Domains\Budget\Services\LoanReminderService;
use App\Domains\Transaction\Services\NextPaymentsService;
use App\Http\Requests\BudgetTargetRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class LoanReminderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['cache.default' => 'array', 'database.default' => 'loan_test', 'database.connections.loan_test' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]);
        Schema::create('users', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('name');
            $table->string('email');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('remember_token')->nullable();
            $table->json('notification_prefs')->nullable();
            $table->timestamps();
        });
        Schema::create('budget_targets', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('team_id');
            $table->integer('user_id');
            $table->integer('category_id');
            $table->string('name');
            $table->string('target_type');
            $table->decimal('amount', 15, 2);
            $table->string('frequency');
            $table->integer('frequency_month_date')->nullable();
            $table->boolean('notify');
            $table->date('completed_at')->nullable();
            $table->date('loan_start_date')->nullable();
            $table->timestamps();
        });
        Schema::create('categories', function (Blueprint $table): void {
            $table->integer('id');
            $table->string('name');
        });
        Schema::create('transactions', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('status');
            $table->timestamp('deleted_at')->nullable();
        });
        Schema::create('transaction_lines', function (Blueprint $table): void {
            $table->increments('id');
            $table->integer('transaction_id');
            $table->integer('team_id');
            $table->integer('category_id');
            $table->integer('type');
            $table->date('date');
            $table->decimal('amount', 15, 2);
        });
        Schema::create('notifications', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    private function target(array $overrides = []): array
    {
        $user = User::factory()->create(['notification_prefs' => ['email' => false, 'push' => false]]);
        $target = BudgetTarget::query()->create([...[
            'team_id' => 2, 'user_id' => $user->id, 'category_id' => 1040,
            'name' => 'Loan vehicle 27th', 'target_type' => 'loan', 'amount' => 500,
            'frequency' => 'MONTHLY', 'frequency_month_date' => 27, 'notify' => true,
        ], ...$overrides]);

        return [$user, $target];
    }

    public function test_reminds_three_days_before_once_per_installment(): void
    {
        [$user, $target] = $this->target();
        $service = new LoanReminderService;
        $this->assertSame(0, $service->sendDueReminders(Carbon::parse('2026-10-23')));
        $this->assertSame(1, $service->sendDueReminders(Carbon::parse('2026-10-24')));
        $this->assertSame(0, $service->sendDueReminders(Carbon::parse('2026-10-24')));
        $this->assertSame(0, $service->sendDueReminders(Carbon::parse('2026-10-25')));
        $payload = $user->notifications()->firstOrFail()->data;
        $this->assertSame($target->id, $payload['target_id']);
        $this->assertSame('2026-10-27', $payload['due_date']);
        $this->assertSame(1, $service->sendDueReminders(Carbon::parse('2026-11-24')));
    }

    public function test_skips_disabled_completed_and_missing_dates(): void
    {
        $this->target(['notify' => false]);
        $this->target(['completed_at' => '2026-10-01']);
        $this->target(['frequency_month_date' => null]);
        $this->target(['target_type' => 'spending']);
        $this->target(['loan_start_date' => '2026-11-01']);
        $this->assertSame(0, (new LoanReminderService)->sendDueReminders(Carbon::parse('2026-10-24')));
    }

    public function test_paid_installment_is_skipped_but_partial_payment_is_not(): void
    {
        $this->target();
        DB::table('transactions')->insert(['id' => 1, 'status' => 'verified']);
        DB::table('transaction_lines')->insert(['transaction_id' => 1, 'team_id' => 2, 'category_id' => 1040, 'type' => -1, 'date' => '2026-10-20', 'amount' => 100]);
        $service = new LoanReminderService;
        $this->assertSame(1, $service->sendDueReminders(Carbon::parse('2026-10-24')));
        DB::table('notifications')->delete();
        DB::table('transaction_lines')->update(['amount' => 500]);
        $this->assertSame(0, $service->sendDueReminders(Carbon::parse('2026-10-24')));
        DB::table('transactions')->update(['status' => 'draft']);
        $this->assertSame(1, $service->sendDueReminders(Carbon::parse('2026-10-24')));
    }

    public function test_handles_short_months_and_next_month_notice(): void
    {
        $this->target(['frequency_month_date' => 31]);
        $service = new LoanReminderService;
        $this->assertSame(1, $service->sendDueReminders(Carbon::parse('2026-02-25')));
        $payload = json_decode(DB::table('notifications')->first()->data, true);
        $this->assertSame('2026-02-28', $payload['due_date']);
        $this->target(['frequency_month_date' => 1]);
        $this->assertSame(2, $service->sendDueReminders(Carbon::parse('2026-10-29')));
        $this->assertSame(0, $service->sendDueReminders(Carbon::parse('2026-11-01')));
    }

    public function test_requires_a_valid_loan_payment_day(): void
    {
        $rules = (new BudgetTargetRequest)->rules();
        $this->assertTrue(Validator::make(['target_type' => 'loan', 'frequency_month_date' => 27, 'notify' => true], $rules)->passes());
        $this->assertTrue(Validator::make(['target_type' => 'loan'], $rules)->fails());
        $this->assertTrue(Validator::make(['target_type' => 'loan', 'frequency_month_date' => 32], $rules)->fails());
    }

    public function test_loan_form_exposes_payment_day_and_enables_reminders_for_new_loans(): void
    {
        $script = <<<'JS'
const fs = require('node:fs');
const vm = require('node:vm');
const assert = require('node:assert/strict');
const ts = require('typescript');
const { parse, compileTemplate } = require('@vue/compiler-sfc');
const filename = 'resources/js/domains/budget/components/BudgetTargetForm.vue';
const { descriptor } = parse(fs.readFileSync(filename, 'utf8'));
assert.deepEqual(compileTemplate({ source: descriptor.template.content, filename, id: 'loan' }).errors, []);
const watchers = [];
const context = {
 reactive: value => value, ref: value => ({ value }), computed: fn => ({ get value() { return fn(); } }),
 watch: (getter, fn) => watchers.push({ getter, fn }), toRefs: p => Object.fromEntries(Object.entries(p).map(([key, value]) => [key, { value }])),
 defineProps: () => ({ category: { id: 1040, name: 'Loan vehicle 27th' } }), defineEmits: () => () => {},
 useI18n: () => ({ t: key => key }), generateRandomColor: () => 'blue', useForm: initial => ({ ...initial }),
 useDatePager: () => ({ selectedSpan: { value: [] } }),
};
vm.createContext(context);
const source = descriptor.scriptSetup.content.replace(/^import .*;\r?\n/gm, '');
vm.runInContext(ts.transpile(source + '\n globalThis.result = state;'), context);
context.result.form.target_type = 'loan';
watchers.find(watcher => watcher.getter() === 'loan').fn('loan', 'spending');
assert.equal(context.result.form.notify, true);
assert.equal(context.result.form.frequency, 'MONTHLY');
const existingLoan = { category: { id: 1040, name: 'Loan vehicle 27th' }, item: { target_type: 'loan', term_months: 12, principal: '6000.00', interest_rate: 0, amount: '500.00', notify: true } };
context.defineProps = () => existingLoan;
context.useForm = initial => ({ ...initial, reset() {}, data() { return initial; } });
watchers.length = 0;
vm.runInContext(ts.transpile('(function() { ' + source + '\n globalThis.result = state; })();'), context);
watchers[0].fn();
assert.equal(context.result.form.term_months, '12');
assert.equal(context.result.form.interest_rate, '0');
assert.equal(context.result.form.amount, '500.00');
assert.ok(descriptor.template.content.includes("['spending', 'loan'].includes(form.target_type)"));
JS;
        $process = new Process(['node'], base_path());
        $process->setInput($script)->run();
        $this->assertTrue($process->isSuccessful(), $process->getErrorOutput());
    }

    public function test_upcoming_payments_include_unpaid_loan_targets(): void
    {
        $this->target();
        DB::table('categories')->insert(['id' => 1040, 'name' => 'Loan vehicle 27th']);
        $service = new NextPaymentsService;
        $method = new \ReflectionMethod($service, 'getUnpaidBudgetCategories');
        $payments = $method->invoke($service, 2, Carbon::parse('2026-10-05'), Carbon::parse('2026-12-31'));
        $this->assertCount(1, $payments);
        $this->assertSame('2026-10-27', $payments[0]['due_date']);
        DB::table('transactions')->insert(['id' => 1, 'status' => 'verified']);
        DB::table('transaction_lines')->insert(['transaction_id' => 1, 'team_id' => 2, 'category_id' => 1040, 'type' => -1, 'date' => '2026-10-20', 'amount' => 100]);
        $this->assertCount(1, $method->invoke($service, 2, Carbon::parse('2026-10-05'), Carbon::parse('2026-12-31')));
        DB::table('transaction_lines')->update(['amount' => 500]);
        $this->assertCount(0, $method->invoke($service, 2, Carbon::parse('2026-10-05'), Carbon::parse('2026-12-31')));
    }
}
