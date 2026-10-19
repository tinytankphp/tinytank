# AGENTS.md

Instructions for AI coding agents working on this site. It's a **TinyTank** site: a small PHP 8.4
framework for mostly-static sites with a little server-side life (Twig, Markdown content, Datastar,
compiled ahead of time). The framework is the Composer package `tinytank/framework` in
`vendor/tinytank/framework/`: never edit it; this site's code is everything else.

**Documentation:** https://docs.tinytank.dev/llms-full.txt (all the docs in one plain-text file;
index: https://docs.tinytank.dev/llms.txt). Every page also exists as Markdown: append `.md` to its
URL, e.g. https://docs.tinytank.dev/1.x/content/pages.md. Read the relevant page before guessing.

<!-- example:start -->
**This site still has TinyTank's starter examples** (listed in `.examples`: the home page demos, a
FAQ, sample posts, an Italian translation). When the user wants their own site rather than the
demos, remove them all, with their tests, with `php bin/console site:clean --force`: don't delete
them by hand. This paragraph goes with them.
<!-- example:end -->

## What TinyTank is not

Don't bring habits from Laravel or Symfony that don't apply here:

- **No database, ORM, migrations, sessions or user accounts.** Content is files; state that must
  persist across visits lives in the browser (`persist()` in `tinytank`).
- **No inline `<script>` and nothing loaded from other hosts.** A strict Content Security Policy
  blocks them and the page fails silently in the browser. JavaScript goes in `resources/js/`
  (bundled by Vite); fonts and icons are self-hosted. Allow a host only if it's truly needed
  (`csp.sources` in `config/app.php`, or `csp_allow()` for one page).
- **No `fetch()` or front-end framework for interactivity:** use Datastar (below).
- **No raw HTML in Markdown:** it's escaped. Use Markdown, or a content component (`::name{…}`).
- **No tracking or third-party embeds by default:** the site is privacy-first (no IP addresses in
  logs, click-to-load videos, metadata stripped from images). Keep it that way.

## Where things go

| To add… | Do this | Docs |
|---|---|---|
| A page of text (About, Team, Services…) | `content/pages/<path>/index.md` with front matter (`title`, `summary`…). The folder is the URL; no route or controller. | `1.x/content/pages` |
| A page only reachable by its link (thank-you, preview) | `unlisted: true` in its front matter: noindex, not in the sitemap; menus filter `where('unlisted', false)` | `1.x/content/pages#unlisted-pages` |
| Repeating items with the same fields (services, team, projects) | a **data collection**: fields in `config/collections.php`, one file per item in `content/<name>/` (YAML or Markdown); query with `collection('name')` | `1.x/content/collections` |
| A blog post | `content/blog/YYYY/MM/<slug>/index.md`; drafts in `content/blog/drafts/` | `1.x/content/posts` |
| A page that computes on each request | a route in `config/routes.php`, a controller in `src/Controller/`, a template in `templates/` | `1.x/getting-started/first-page` |
| Browser-only interactivity (toggle, counter, tabs) | Datastar signals: `data-signals="{open: false}"`, `data-on:click="$open = !$open"`, `data-show="$open"` | `1.x/basics/datastar` |
| Interactivity the server decides (search, forms, lookups) | a template action: `{{ datastar.post('_partials/x') }}` renders `templates/_partials/x.twig` with the browser's `signals` | `1.x/basics/datastar` |
| A value every template needs (social links, phone, address) | `config/globals.php` returns an array; templates read `{{ globals.socials.github }}`. Public: no secrets | `1.x/basics/templates#global-values` |
| A form that emails | `config/forms.php` and `form:` in a content page's front matter | `1.x/features/forms` |
| Images | put them next to the Markdown and reference them (`![Alt](photo.jpg)`): responsive AVIF/WebP automatically; in Twig, `image()` | `1.x/features/images` |
| A reusable block inside Markdown | a content component: `::name{key="value"}` on its own line renders `templates/_components/name.twig` | `1.x/content/components` |
| UI texts in several languages | `{{ 'Text'|t }}` and `translations/<code>.php` | `1.x/features/languages` |
| A console command or deploy step | `src/Command/`, `$app->addDeployStep()` in `config/bootstrap.php` | `1.x/extending/commands` |

Templates **query** content; nothing is injected: `pages()`, `posts()`, `collection('name')`, with
`.where()`, `.orderBy()`, `.limit()`, `.one()`, `.all()` (see `1.x/basics/querying`).

## Rules that trip people up

- **A route and a content page can't share a URL.** Don't add an `/about` route while
  `content/pages/about/` exists; `deploy` refuses.
- **Front matter keys are strict:** an unknown key is an error naming the file. Put custom values
  under `data:`. Dates are `YYYY-MM-DD` and must be real days.
- **A Datastar partial that queues an event sends only its events.** If it calls `patch_signals`
  and must also update HTML, wrap the HTML in `{% apply patch_elements %}…{% endapply %}`.
- **ARIA states are strings:** `data-attr` writes a boolean as a bare attribute or removes it, so use
  `data-attr:aria-expanded="String($open)"`, not `="$open"`.
- **Datastar actions are bound to their method:** a `datastar.post()` URL answers only POST.
  Changes that need authorization belong in a controller, not a template action.
- **Pages need assets:** run `npm run dev` (with `APP_DEBUG=1`) or `npm run build`; without either,
  pages fail with "Vite manifest not found".
- **Pages are public and cacheable** (ETag, optional CDN). Never render visitor-specific data in a
  page; send `Cache-Control: private` from the controller if you must.
- **`page` and `blog_post` are reserved route names** (TinyTank writes translated URLs for them):
  link with `path('page', {path: 'about'})` and `path('blog_post', {slug: post.slug})`.

## Commands

```sh
ddev start                                # https://<project>.ddev.site (DDEV); or, without DDEV:
php -S localhost:8000 -t public public/index.php
npm run dev                               # Vite dev server (or: npm run build)
composer test                             # PHPUnit (tests/)
composer analyse                          # PHPStan, level 8
CHROMIUM_PATH=/usr/bin/chromium npm run test:e2e   # Playwright, against a build (npm run build)
php bin/console deploy                    # production: compile everything (see the deploy docs)
php bin/console cache:clear               # back to development after a local deploy
```

With DDEV, prefix commands with `ddev` (`ddev composer test`, `ddev npm run dev`).

## Before you finish

- `composer test`, `composer analyse` and the browser tests pass. The skeleton's tests check its
  starter content: when you change a starter page, update its tests (the list is in
  `1.x/getting-started/checklist`); the examples go all at once with `site:clean`.
- Changed content or templates show in development right away; in production they need
  `php bin/console deploy`.
- No secrets in templates, JavaScript or `public` config values: secrets live in `.env`.
