<?php

declare(strict_types=1);

// Pages that moved: the old path and where it went, so old links and search results keep working.
// Checked first, before languages and routes; the query string is kept.
//
//   '/old-page' => '/new-page',                            // 301, moved for good
//   '/it/vecchia' => '/it/nuova',                          // each language's path as it is
//   '/summer-sale' => ['to' => '/offers', 'status' => 302], // temporary
//   '/docs' => 'https://docs.example.com',                 // another site
//
// `bin/console check` reports an old path that still has a page, and a target that doesn't answer.
// https://docs.tinytank.dev/1.x/basics/routing#redirects

return [
];
