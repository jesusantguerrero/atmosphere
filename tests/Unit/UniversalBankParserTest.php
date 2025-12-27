<?php

namespace Tests\Unit;

use App\Domains\Automation\Models\Automation;
use App\Domains\Automation\Models\AutomationTaskAction;
use App\Domains\Integration\Actions\APAP\APAPAlert;
use App\Domains\Integration\Actions\BHDAlert;
use App\Domains\Integration\Actions\BSC\BSCAlert;
use App\Domains\Integration\Actions\UniversalBankParser;
use App\Exceptions\UnsupportedBankException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionClass;
use Tests\TestCase;

class UniversalBankParserTest extends TestCase
{
    use RefreshDatabase;

    protected Automation $automation;

    protected AutomationTaskAction $task;

    protected AutomationTaskAction $previousTask;

    protected AutomationTaskAction $trigger;

    protected function setUp(): void
    {
        parent::setUp();

        // Create automation with bank patterns config
        $this->automation = Automation::factory()->create([
            'config' => [
                'bank_patterns' => UniversalBankParser::getAllBankPatterns(),
            ],
        ]);

        // Create mock task actions
        $this->task = AutomationTaskAction::factory()->create([
            'automation_id' => $this->automation->id,
        ]);

        $this->previousTask = AutomationTaskAction::factory()->create([
            'automation_id' => $this->automation->id,
        ]);

        $this->trigger = AutomationTaskAction::factory()->create([
            'automation_id' => $this->automation->id,
        ]);
    }

    /** @test */
    public function it_detects_bhd_bank_from_exact_email()
    {
        $reflection = new ReflectionClass(UniversalBankParser::class);
        $method = $reflection->getMethod('detectBank');
        $method->setAccessible(true);

        $bankCode = $method->invoke(null, $this->automation, 'alertas@bhd.com.do', '');

        $this->assertEquals('BHD', $bankCode);
    }

    /** @test */
    public function it_detects_bhd_bank_from_second_email_address()
    {
        $reflection = new ReflectionClass(UniversalBankParser::class);
        $method = $reflection->getMethod('detectBank');
        $method->setAccessible(true);

        $bankCode = $method->invoke(null, $this->automation, 'notificaciones@bhd.com.do', '');

        $this->assertEquals('BHD', $bankCode);
    }

    /** @test */
    public function it_detects_bhd_bank_from_domain()
    {
        $reflection = new ReflectionClass(UniversalBankParser::class);
        $method = $reflection->getMethod('detectBank');
        $method->setAccessible(true);

        $bankCode = $method->invoke(null, $this->automation, 'custom@bhd.com.do', '');

        $this->assertEquals('BHD', $bankCode);
    }

    /** @test */
    public function it_detects_apap_bank_from_email()
    {
        $reflection = new ReflectionClass(UniversalBankParser::class);
        $method = $reflection->getMethod('detectBank');
        $method->setAccessible(true);

        $bankCode = $method->invoke(null, $this->automation, 'NO-REPLY@apap.com.do', '');

        $this->assertEquals('APAP', $bankCode);
    }

    /** @test */
    public function it_detects_apap_bank_from_domain()
    {
        $reflection = new ReflectionClass(UniversalBankParser::class);
        $method = $reflection->getMethod('detectBank');
        $method->setAccessible(true);

        $bankCode = $method->invoke(null, $this->automation, 'test@apap.com.do', '');

        $this->assertEquals('APAP', $bankCode);
    }

    /** @test */
    public function it_detects_bsc_bank_from_email()
    {
        $reflection = new ReflectionClass(UniversalBankParser::class);
        $method = $reflection->getMethod('detectBank');
        $method->setAccessible(true);

        $bankCode = $method->invoke(null, $this->automation, 'notificaciones@bsc.com.do', '');

        $this->assertEquals('BSC', $bankCode);
    }

    /** @test */
    public function it_detects_bsc_bank_from_domain()
    {
        $reflection = new ReflectionClass(UniversalBankParser::class);
        $method = $reflection->getMethod('detectBank');
        $method->setAccessible(true);

        $bankCode = $method->invoke(null, $this->automation, 'alerts@bsc.com.do', '');

        $this->assertEquals('BSC', $bankCode);
    }

    /** @test */
    public function it_returns_null_for_unknown_bank()
    {
        $reflection = new ReflectionClass(UniversalBankParser::class);
        $method = $reflection->getMethod('detectBank');
        $method->setAccessible(true);

        $bankCode = $method->invoke(null, $this->automation, 'unknown@example.com', '');

        $this->assertNull($bankCode);
    }

    /** @test */
    public function it_detects_bank_case_insensitively()
    {
        $reflection = new ReflectionClass(UniversalBankParser::class);
        $method = $reflection->getMethod('detectBank');
        $method->setAccessible(true);

        $bankCode1 = $method->invoke(null, $this->automation, 'ALERTAS@BHD.COM.DO', '');
        $bankCode2 = $method->invoke(null, $this->automation, 'no-reply@APAP.COM.DO', '');
        $bankCode3 = $method->invoke(null, $this->automation, 'NOTIFICACIONES@BSC.COM.DO', '');

        $this->assertEquals('BHD', $bankCode1);
        $this->assertEquals('APAP', $bankCode2);
        $this->assertEquals('BSC', $bankCode3);
    }

    /** @test */
    public function it_returns_bhd_parser_for_bhd_code()
    {
        $reflection = new ReflectionClass(UniversalBankParser::class);
        $method = $reflection->getMethod('getBankParser');
        $method->setAccessible(true);

        $parser = $method->invoke(null, 'BHD');

        $this->assertInstanceOf(BHDAlert::class, $parser);
    }

    /** @test */
    public function it_returns_apap_parser_for_apap_code()
    {
        $reflection = new ReflectionClass(UniversalBankParser::class);
        $method = $reflection->getMethod('getBankParser');
        $method->setAccessible(true);

        $parser = $method->invoke(null, 'APAP');

        $this->assertInstanceOf(APAPAlert::class, $parser);
    }

    /** @test */
    public function it_returns_bsc_parser_for_bsc_code()
    {
        $reflection = new ReflectionClass(UniversalBankParser::class);
        $method = $reflection->getMethod('getBankParser');
        $method->setAccessible(true);

        $parser = $method->invoke(null, 'BSC');

        $this->assertInstanceOf(BSCAlert::class, $parser);
    }

    /** @test */
    public function it_throws_exception_for_unsupported_bank_code()
    {
        $this->expectException(UnsupportedBankException::class);
        $this->expectExceptionMessage("Bank 'UNKNOWN' is not supported by the parser registry");

        $reflection = new ReflectionClass(UniversalBankParser::class);
        $method = $reflection->getMethod('getBankParser');
        $method->setAccessible(true);

        $method->invoke(null, 'UNKNOWN');
    }

    /** @test */
    public function it_validates_email_data_requires_from_field()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Email sender (from) is required');

        UniversalBankParser::handle(
            $this->automation,
            ['subject' => 'Test'],
            $this->task,
            $this->previousTask,
            $this->trigger
        );
    }

    /** @test */
    public function it_validates_email_format()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid email format');

        UniversalBankParser::handle(
            $this->automation,
            ['from' => 'not-an-email'],
            $this->task,
            $this->previousTask,
            $this->trigger
        );
    }

    /** @test */
    public function it_returns_null_for_unknown_bank_in_handle()
    {
        $payload = [
            'from' => 'unknown@example.com',
            'subject' => 'Test Email',
            'message' => '<html><body>Test</body></html>',
            'id' => '123',
            'date' => '2024-01-01',
            'messageId' => 'msg123',
        ];

        $result = UniversalBankParser::handle(
            $this->automation,
            $payload,
            $this->task,
            $this->previousTask,
            $this->trigger
        );

        $this->assertNull($result);
    }

    /** @test */
    public function it_has_correct_name()
    {
        $parser = new UniversalBankParser;

        $this->assertEquals('UniversalBankParser', $parser->getName());
    }

    /** @test */
    public function it_has_correct_label()
    {
        $parser = new UniversalBankParser;

        $this->assertEquals('Universal Bank Parser', $parser->label());
    }

    /** @test */
    public function it_has_descriptive_description()
    {
        $parser = new UniversalBankParser;

        $description = $parser->getDescription();

        $this->assertStringContainsString('multiple banks', $description);
        $this->assertStringContainsString('BHD', $description);
        $this->assertStringContainsString('APAP', $description);
    }

    /** @test */
    public function it_gets_bank_config_from_automation()
    {
        $reflection = new ReflectionClass(UniversalBankParser::class);
        $method = $reflection->getMethod('getBankConfig');
        $method->setAccessible(true);

        $config = $method->invoke(null, $this->automation);

        $this->assertArrayHasKey('BHD', $config);
        $this->assertArrayHasKey('APAP', $config);
        $this->assertEquals(['alertas@bhd.com.do', 'notificaciones@bhd.com.do'], $config['BHD']['email_addresses']);
    }

    /** @test */
    public function it_returns_empty_array_when_no_bank_patterns_configured()
    {
        $automationWithoutConfig = Automation::factory()->create([
            'config' => [],
        ]);

        $reflection = new ReflectionClass(UniversalBankParser::class);
        $method = $reflection->getMethod('getBankConfig');
        $method->setAccessible(true);

        $config = $method->invoke(null, $automationWithoutConfig);

        $this->assertIsArray($config);
        $this->assertEmpty($config);
    }
}
