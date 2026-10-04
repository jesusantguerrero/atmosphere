<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicMarketingPagesTest extends TestCase
{
    public function test_landing_page_renders_for_guest(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Loger');
        $response->assertSee(__('landing.hero.cta_primary'));
    }

    public function test_landing_includes_open_graph_and_twitter_meta(): void
    {
        $response = $this->get('/');

        $response->assertSee('property="og:title"', false);
        $response->assertSee('property="og:description"', false);
        $response->assertSee('property="og:image"', false);
        $response->assertSee('name="twitter:card"', false);
    }

    public function test_marketing_pages_use_the_dark_brand_asset_without_color_filters(): void
    {
        foreach (['/', '/pricing'] as $uri) {
            $response = $this->get($uri);

            $response->assertOk();
            $response->assertSee('src="/logo-dark.svg"', false);
            $response->assertDontSee('brightness-0 invert', false);
        }
    }

    public function test_installable_brand_icons_match_the_manifest(): void
    {
        $manifest = json_decode(file_get_contents(public_path('manifest.json')), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('Loger', $manifest['name']);
        $this->assertSame('standalone', $manifest['display']);
        $this->assertSame('#121319', $manifest['theme_color']);
        $this->assertCount(4, $manifest['icons']);

        foreach ($manifest['icons'] as $icon) {
            $dimensions = getimagesize(public_path(ltrim($icon['src'], '/')));

            $this->assertSame($icon['sizes'], $dimensions[0].'x'.$dimensions[1]);
            $this->assertSame('image/png', $dimensions['mime']);
        }

        $appleIcon = getimagesize(public_path('apple-touch-icon.png'));
        $this->assertSame([180, 180], [$appleIcon[0], $appleIcon[1]]);

        $favicon = file_get_contents(public_path('favicon.ico'));
        $header = unpack('vreserved/vtype/vcount', substr($favicon, 0, 6));
        $this->assertSame(['reserved' => 0, 'type' => 1, 'count' => 3], $header);

        foreach ([16, 32, 48] as $index => $size) {
            $entry = substr($favicon, 6 + $index * 16, 16);
            $this->assertSame($size, ord($entry[0]));
            $this->assertSame($size, ord($entry[1]));
        }
    }

    public function test_landing_includes_json_ld_structured_data(): void
    {
        $response = $this->get('/');

        $response->assertSee('application/ld+json', false);
        $response->assertSee('"SoftwareApplication"', false);
    }

    public function test_landing_renders_in_spanish_via_lang_query(): void
    {
        $response = $this->get('/?lang=es');

        $response->assertStatus(200);
        $response->assertSee(__('landing.hero.cta_primary', [], 'es'));
    }

    public function test_landing_ignores_unsupported_locales(): void
    {
        $response = $this->get('/?lang=zz');

        $response->assertStatus(200);
        $response->assertSee(__('landing.hero.cta_primary', [], 'en'));
    }

    public function test_pricing_page_renders(): void
    {
        $response = $this->get('/pricing');

        $response->assertStatus(200);
        $response->assertSee(__('landing.pricing.free.name'));
        $response->assertSee(__('landing.pricing.plus.name'));
        $response->assertSee('RD$299');
    }

    public function test_privacy_policy_page_renders(): void
    {
        $response = $this->get('/privacy-policy');

        $response->assertStatus(200);
        $response->assertSee(__('landing.footer.privacy'));
    }

    public function test_terms_of_service_page_renders(): void
    {
        $response = $this->get('/terms-of-service');

        $response->assertStatus(200);
        $response->assertSee(__('landing.footer.terms'));
    }

    public function test_sitemap_xml_returns_xml_with_public_routes(): void
    {
        $response = $this->get('/sitemap.xml');

        $response->assertStatus(200);
        $this->assertStringStartsWith('application/xml', $response->headers->get('Content-Type'));
        $response->assertSee('<urlset', false);
        $response->assertSee(route('landing'), false);
        $response->assertSee(route('pricing'), false);
    }

    public function test_robots_txt_references_sitemap(): void
    {
        $contents = file_get_contents(public_path('robots.txt'));

        $this->assertStringContainsString('Sitemap:', $contents);
    }

    public function test_analytics_partial_renders_when_id_configured(): void
    {
        config(['services.google_analytics.measurement_id' => 'G-TEST123']);

        $response = $this->get('/');

        $response->assertSee('googletagmanager.com/gtag/js?id=G-TEST123', false);
    }

    public function test_analytics_partial_omitted_when_no_id(): void
    {
        config(['services.google_analytics.measurement_id' => null]);

        $response = $this->get('/');

        $response->assertDontSee('googletagmanager.com', false);
    }
}
