<?php

declare(strict_types=1);

use App\Controller\BlogController;
use App\Controller\ClockController;
use App\Controller\HomeController;
use App\Controller\PageController;
use TinyTank\Blog\AssetController;
use TinyTank\Blog\Blog;
use TinyTank\Blog\FeedController;
use TinyTank\Collections\AssetController as CollectionAssetController;
use TinyTank\Collections\Collections;
use TinyTank\Kernel;
use TinyTank\Pages\AssetController as PageAssetController;
use TinyTank\Pages\Pages;
use TinyTank\Seo\RobotsController;
use TinyTank\Seo\SitemapController;

// Handlers: [Controller::class, 'method'], an invokable Controller::class, or a closure.
return static function (Kernel $app): void {
    // Pages
    $app->get('/', HomeController::class, 'home');
    $app->get('/blog', [BlogController::class, 'index'], 'blog');
    $app->get('/blog/page/{page}', [BlogController::class, 'index'], 'blog_page', ['page' => '[1-9][0-9]{0,5}']);
    $app->get('/blog/{slug}', [BlogController::class, 'show'], 'blog_post', ['slug' => '[a-z0-9]+(?:-[a-z0-9]+)*']);
    // Post images and files. In production `deploy` copies them to public/, so this only runs in development.
    $app->get(Blog::ASSET_URL . '/{slug}/{file}', AssetController::class, 'blog_asset', ['file' => '.+']);
    // The files of collection items that are folders (content/<collection>/<slug>/index.md), likewise.
    $app->get(Collections::ASSET_URL . '/{file}', CollectionAssetController::class, 'collection_asset', ['file' => '.+']);

    // Feeds for crawlers and readers (framework controllers)
    $app->get('/blog/feed.xml', FeedController::class, 'blog_feed');
    $app->get('/sitemap.xml', SitemapController::class, 'sitemap');
    $app->get('/robots.txt', RobotsController::class, 'robots');

    // Datastar actions
    $app->post('/clock', ClockController::class, 'clock');

    // Content pages: content/pages/privacy/index.md is /privacy. A catch-all, so it has a low priority:
    // every other route wins, wherever it's added. `deploy` reports pages that a route hides.
    $app->get(Pages::ASSET_URL . '/{file}', PageAssetController::class, 'page_asset', ['file' => '.+']);
    // POST too, for pages with a form (`form:` in their front matter), like the contact page.
    $app->route(['GET', 'POST'], '/{path}', [PageController::class, 'show'], 'page', ['path' => Pages::PATH], priority: -1);
    // Link to a page by its folder path, `path('page', {path: 'about'})`: TinyTank writes each
    // language's URL, translated slugs included.
};
