<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Production stored the Passport keys with escaped line breaks, so every
 * line kept a trailing "\" and OpenSSL refused them ("Invalid key supplied"),
 * which made /mcp and /oauth/token answer 500.
 */
class PassportKeyConfigTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($_SERVER['PASSPORT_PRIVATE_KEY'], $_ENV['PASSPORT_PRIVATE_KEY'], $_SERVER['PASSPORT_PUBLIC_KEY'], $_ENV['PASSPORT_PUBLIC_KEY']);
        parent::tearDown();
    }

    /**
     * @return array<string, array{0: \Closure(string): string}>
     */
    public static function pastedForms(): array
    {
        return [
            'real line breaks' => [fn (string $pem) => $pem],
            'one line with \n' => [fn (string $pem) => str_replace("\n", '\n', $pem)],
            'one line with \\\\n' => [fn (string $pem) => str_replace("\n", '\\\\n', $pem)],
            'backslash at each line end' => [fn (string $pem) => str_replace("\n", "\\\n", $pem)],
            'windows line breaks' => [fn (string $pem) => str_replace("\n", "\r\n", $pem)],
        ];
    }

    #[DataProvider('pastedForms')]
    public function test_the_keys_are_readable_however_they_were_pasted(\Closure $paste): void
    {
        $privatePem = str_replace("\r\n", "\n", file_get_contents(__DIR__.'/../Fixtures/oauth/oauth-private.key'));
        $publicPem = str_replace("\r\n", "\n", file_get_contents(__DIR__.'/../Fixtures/oauth/oauth-public.key'));

        $_SERVER['PASSPORT_PRIVATE_KEY'] = $_ENV['PASSPORT_PRIVATE_KEY'] = $paste($privatePem);
        $_SERVER['PASSPORT_PUBLIC_KEY'] = $_ENV['PASSPORT_PUBLIC_KEY'] = $paste($publicPem);

        $config = require __DIR__.'/../../config/passport.php';

        $this->assertNotFalse(openssl_pkey_get_private($config['private_key']));
        $this->assertNotFalse(openssl_pkey_get_public($config['public_key']));
    }

    public function test_missing_keys_stay_null_so_passport_falls_back_to_key_files(): void
    {
        $config = require __DIR__.'/../../config/passport.php';

        $this->assertNull($config['private_key']);
        $this->assertNull($config['public_key']);
    }
}
