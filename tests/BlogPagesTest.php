<?php

declare(strict_types=1);

namespace App\Tests;

final class BlogPagesTest extends AppTestCase
{
    public function testFirstPageWithNumberedPages(): void
    {
        $html = self::body($this->request($app = $this->app(), '/blog'));

        self::assertStringContainsString('<title>Blog · ' . $app->site->name() . '</title>', $html);
        self::assertStringContainsString('href="/blog/first"', $html);
        self::assertStringNotContainsString('href="/blog/second"', $html);
        self::assertStringContainsString('rel="next" href="/blog/page/2"', $html);
        self::assertStringNotContainsString('rel="prev"', $html);
        self::assertMatchesRegularExpression('#<a href="/blog/page/3"[^>]*>3</a>#', $html, 'numbered pages, without JavaScript too');
        self::assertStringNotContainsString('Load more', $html, 'pages have URLs: no "Load more"');
    }

    public function testMiddlePage(): void
    {
        $html = self::body($this->request($app = $this->app(), '/blog/page/2'));

        self::assertStringContainsString('<title>Blog · Page 2 · ' . $app->site->name() . '</title>', $html);
        self::assertStringContainsString('<link rel="canonical" href="https://example.test/blog/page/2">', $html);
        self::assertStringContainsString('href="/blog/second"', $html);
        self::assertStringContainsString('rel="prev" href="/blog"', $html);
        self::assertStringContainsString('rel="next" href="/blog/page/3"', $html);
        self::assertStringContainsString('<span aria-current="page"', $html);
    }

    public function testLastPageHasNoNextLinkOrLoadMore(): void
    {
        $html = self::body($this->request($this->app(), '/blog/page/3'));

        self::assertStringContainsString('rel="prev" href="/blog/page/2"', $html);
        self::assertStringNotContainsString('rel="next"', $html);
        self::assertStringNotContainsString('Load more', $html);
    }

    public function testPageOneRedirectsToTheBlogInTheSameLanguage(): void
    {
        $app = $this->app();

        self::assertSame('/blog', $this->request($app, '/blog/page/1')->headers->get('Location'));
        self::assertSame(301, $this->request($app, '/blog/page/1')->getStatusCode());
        // example:start (the sample translation)
        self::assertSame('/it/blog', $this->request($app, '/it/blog/page/1')->headers->get('Location'));
        // example:end
    }

    public function testPagesPastTheEndAre404(): void
    {
        $app = $this->app();

        self::assertSame(404, $this->request($app, '/blog/page/4')->getStatusCode());
        self::assertSame(404, $this->request($app, '/blog/page/0')->getStatusCode());
        self::assertSame(404, $this->request($app, '/blog/page/99999999999999999999')->getStatusCode(), 'not a 500');
        // example:start
        $it = $this->request($app, '/it/blog/page/2');
        self::assertSame(404, $it->getStatusCode(), 'Italian has one post, so one page');
        self::assertStringContainsString('Pagina non trovata.', self::body($it));
        // example:end
    }

    // example:start (the sample translation: `php bin/console site:clean` removes these tests with it)
    public function testPagesExistingInOneLanguageOnlyGetAFallbackInTheSwitcher(): void
    {
        $html = self::body($this->request($this->app(), '/blog/page/2'));

        self::assertStringContainsString('hreflang="en" href="https://example.test/blog/page/2"', $html);
        self::assertStringNotContainsString('hreflang="it"', $html);
        self::assertMatchesRegularExpression('#<a href="/it/blog" lang="it"\s+class=#', $html, 'no hreflang on a fallback link');
    }

    public function testItalianBlogListsOnlyTranslatedPosts(): void
    {
        $html = self::body($this->request($this->app(), '/it/blog'));

        self::assertStringContainsString('<html lang="it">', $html);
        self::assertStringContainsString('href="/it/blog/first"', $html);
        self::assertStringContainsString('Primo articolo', $html);
        self::assertStringNotContainsString('/it/blog/second', $html);
        self::assertStringContainsString('placeholder="Cerca articoli…"', $html);
        self::assertStringContainsString('15 settembre 2026', $html);
    }

    public function testTranslatedPostLinksItsOtherVersion(): void
    {
        $html = self::body($this->request($app = $this->app(), '/it/blog/first'));

        self::assertStringContainsString('<title>Primo articolo · ' . $app->site->name('it') . '</title>', $html);
        self::assertStringContainsString('hreflang="en" href="https://example.test/blog/first"', $html);
        self::assertStringContainsString('hreflang="it" href="https://example.test/it/blog/first"', $html);
        self::assertStringContainsString('<meta property="article:tag" content="news">', $html, 'tags inherited from index.md');
    }

    public function testUntranslatedPostIs404WithALinkToTheExistingVersion(): void
    {
        $response = $this->request($this->app(), '/it/blog/second');

        self::assertSame(404, $response->getStatusCode());
        self::assertStringContainsString('Articolo non trovato.', self::body($response));
        self::assertMatchesRegularExpression('#<a href="/blog/second" lang="en" hreflang="en"#', self::body($response));
    }
    // example:end

    public function testSearchFiltersAndPagesThroughTheResultsWithLoadMore(): void
    {
        $app = $this->app();
        $search = $this->datastarUrl(self::body($this->request($app, '/blog')), '_partials/blog-results');

        $results = self::body($this->request($app, $search . '&datastar=' . rawurlencode('{"q":"","tag":"guide","page":1}')));
        self::assertStringContainsString('href="/blog/second"', $results);
        self::assertStringNotContainsString('href="/blog/first"', $results);
        self::assertStringContainsString('1 of 2 posts', $results);
        self::assertStringContainsString('Load more', $results);
        self::assertStringNotContainsString('rel="next"', $results, 'filtered results have no page URLs');

        // "Load more" appends the next match and replaces the pager.
        $more = $this->datastarUrl($results, '_partials/blog-more');
        $next = self::body($this->request($app, $more . '&datastar=' . rawurlencode('{"q":"","tag":"guide","page":2}'), 'GET', ['Datastar-Request' => 'true']));
        self::assertStringContainsString("data: selector #blog-posts\ndata: mode append", $next);
        self::assertStringContainsString('href="/blog/third"', $next);
        self::assertStringContainsString('<nav id="blog-pager"', $next);
        self::assertStringNotContainsString('Load more', $next, 'no more matches');
    }

    public function testEveryPostForTheSearchOnAStaticSite(): void
    {
        $response = $this->request($this->app(), '/blog/search');
        $html = self::body($response);

        self::assertSame('noindex', $response->headers->get('X-Robots-Tag'), 'not a page of its own');
        self::assertStringStartsWith('<div id="blog-search"', ltrim($html), 'a fragment Datastar puts in place');
        self::assertSame(3, substr_count($html, 'data-words='), 'every post, not a page of them');
        self::assertMatchesRegularExpression('#data-tags="[^"]*guide[^"]*"\s+data-show="\(\$tag#', $html);
    }

    public function testFeedPerLanguage(): void
    {
        $app = $this->app();

        self::assertSame(3, substr_count(self::body($this->request($app, '/blog/feed.xml')), '<entry>'));
        // example:start
        $it = self::body($this->request($app, '/it/blog/feed.xml'));
        self::assertSame(1, substr_count($it, '<entry>'));
        self::assertStringContainsString('<title>Primo articolo</title>', $it);
        // example:end
    }
}
