<?php

declare(strict_types=1);

namespace App\Tests;

use TinyTank\Kernel;
use TinyTank\Testing\KernelTestCase;

/**
 * Boots this site (its config/, controllers and templates) with the fixture content in
 * tests/data/content, one post per page and a throw-away cache directory.
 * Without a Vite build, pages render without their asset tags and the tests that check assets are
 * skipped (requireViteBuild()): run `npm run build` to run them too, as CI does.
 */
abstract class AppTestCase extends KernelTestCase
{
    protected const ROOT = __DIR__ . '/..';

    /** @param array<string, mixed> $overrides */
    protected function app(bool $debug = false, array $overrides = []): Kernel
    {
        return $this->bootKernel(self::ROOT, $debug, array_replace_recursive([
            'content_dir' => __DIR__ . '/data/content',
            'blog' => ['per_page' => 1],
        ], $overrides));
    }

    /**
     * The URL of the first Datastar action on a page (`@get(...)`, `@post(...)`…) whose signed config
     * renders $template; with $method, only actions of that method. Request it with the same method:
     *
     *   $url = $this->datastarUrl($html, '_partials/count', 'post');
     *   $this->request($app, $url, 'POST', ['Sec-Fetch-Site' => 'same-origin'], '{"count":1}');
     */
    protected function datastarUrl(string $html, string $template, ?string $method = null): string
    {
        $methods = $method !== null ? preg_quote(strtolower($method), '#') : 'get|post|put|patch|delete';
        preg_match_all('#@(?:' . $methods . ')\(&quot;([^&]+(?:&amp;[^&]+)*)&quot;\)#', $html, $matches);
        foreach ($matches[1] as $url) {
            $url = html_entity_decode($url);
            parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
            $payload = explode('.', is_string($query['config'] ?? null) ? $query['config'] : '')[0];
            $config = json_decode((string) base64_decode(strtr($payload, '-_', '+/')), true);
            if (($config['t'] ?? null) === $template) {
                return $url;
            }
        }
        self::fail("No Datastar URL for {$template}.");
    }
}
