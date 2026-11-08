<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ThemeContrastTest extends TestCase
{
    /**
     * Text tokens that must reach WCAG AA (4.5:1) on every surface they sit on.
     *
     * @var list<string>
     */
    private const TEXT_TOKENS = ['body', 'body-1', 'primary-ink', 'success-ink', 'warning-ink', 'info-ink', 'error'];

    /**
     * @var list<string>
     */
    private const SURFACES = ['base', 'base-lvl-1', 'base-lvl-2', 'base-lvl-3'];

    public function test_light_and_dark_text_tokens_meet_aa_on_every_surface(): void
    {
        foreach (['light' => $this->tokens(':root'), 'dark' => $this->tokens('.dark')] as $theme => $tokens) {
            foreach (self::TEXT_TOKENS as $text) {
                foreach (self::SURFACES as $surface) {
                    $ratio = $this->contrast($tokens[$text], $tokens[$surface]);
                    $this->assertGreaterThanOrEqual(4.5, $ratio, sprintf('%s: --c-%s on --c-%s is %.2f:1', $theme, $text, $surface, $ratio));
                }
            }
            $buttonRatio = $this->contrast($tokens['on-primary'], $tokens['primary']);
            $this->assertGreaterThanOrEqual(4.5, $buttonRatio, sprintf('%s: --c-on-primary on --c-primary is %.2f:1', $theme, $buttonRatio));
        }
    }

    public function test_surfaces_are_neutral_and_never_pure_black_or_white(): void
    {
        foreach (['light' => $this->tokens(':root'), 'dark' => $this->tokens('.dark')] as $theme => $tokens) {
            foreach (self::SURFACES as $surface) {
                [$red, $green, $blue] = $tokens[$surface];
                $this->assertLessThanOrEqual(2, max($red, $green, $blue) - min($red, $green, $blue), "{$theme}: --c-{$surface} has a color tint");
                $this->assertNotSame([255, 255, 255], $tokens[$surface], "{$theme}: --c-{$surface} is pure white");
                $this->assertNotSame([0, 0, 0], $tokens[$surface], "{$theme}: --c-{$surface} is pure black");
            }
        }
    }

    /**
     * @return array<string, array{int, int, int}>
     */
    private function tokens(string $selector): array
    {
        $css = file_get_contents(dirname(__DIR__, 2).'/resources/css/app.css');
        preg_match('/^'.preg_quote($selector, '/').'\s*\{(.*?)^\}/ms', $css, $block);
        preg_match_all('/--c-([a-z0-9-]+):\s*(\d+)\s+(\d+)\s+(\d+);/i', $block[1] ?? '', $matches, PREG_SET_ORDER);

        $tokens = [];
        foreach ($matches as [, $name, $red, $green, $blue]) {
            $tokens[$name] = [(int) $red, (int) $green, (int) $blue];
        }

        return $tokens;
    }

    /**
     * @param  array{int, int, int}  $first
     * @param  array{int, int, int}  $second
     */
    private function contrast(array $first, array $second): float
    {
        $lighter = max($this->luminance($first), $this->luminance($second));
        $darker = min($this->luminance($first), $this->luminance($second));

        return ($lighter + 0.05) / ($darker + 0.05);
    }

    /**
     * @param  array{int, int, int}  $color
     */
    private function luminance(array $color): float
    {
        [$red, $green, $blue] = array_map(function (int $channel): float {
            $value = $channel / 255;

            return $value <= 0.03928 ? $value / 12.92 : (($value + 0.055) / 1.055) ** 2.4;
        }, $color);

        return 0.2126 * $red + 0.7152 * $green + 0.0722 * $blue;
    }
}
