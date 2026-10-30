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
  logs, click-to-load videos, metadata stripped from images). Keep it that way. The one optional
  service is Pushover for alerts (`PUSHOVER_TOKEN`, `PUSHOVER_USER`), off unless both are set.

## Where things go

| To add… | Do this | Docs |
|---|---|---|
| A page of text (About, Team, Services…) | `content/pages/<path>/index.md` with front matter (`title`, `summary`…). The folder is the URL; no route or controller. | `1.x/content/pages` |
| A page only reachable by its link (thank-you, preview) | `unlisted: true` in its front matter: noindex, not in the sitemap; menus filter `where('unlisted', false)` | `1.x/content/pages#unlisted-pages` |
| Repeating items with the same fields (services, team, projects) | a **data collection**: fields in `config/collections.php`, one file per item in `content/<name>/` (YAML or Markdown); query with `collection('name')`. An item with images of its own is a folder, `content/<name>/<slug>/index.md` plus files (`image: cover.jpg`, responsive through `image()`) | `1.x/content/collections` |
| Several pages sharing one template (reviews, case studies under one parent) | child pages of one parent; `children: {template: pages/review.twig}` in the parent's front matter instead of `template:` in each | `1.x/content/pages#templates` |
| A blog post | `content/blog/YYYY/MM/<slug>/index.md`; drafts in `content/blog/drafts/` | `1.x/content/posts` |
| A page that computes on each request | a route in `config/routes.php`, a controller in `src/Controller/`, a template in `templates/` | `1.x/getting-started/first-page` |
| Browser-only interactivity (toggle, counter, tabs) | Datastar signals: `data-signals="{open: false}"`, `data-on:click="$open = !$open"`, `data-show="$open"` | `1.x/basics/datastar` |
| Interactivity the server decides (search, forms, lookups) | a template action: `{{ datastar.post('_partials/x') }}` renders `templates/_partials/x.twig` with the browser's `signals` | `1.x/basics/datastar` |
| A value every template needs (social links, phone, address) | `config/globals.php` returns an array; templates read `{{ globals.socials.github }}`. Public: no secrets | `1.x/basics/templates#global-values` |
| A page that moved or was renamed (keep the old URL working) | `config/redirects.php`: `'/old-path' => '/new-path'` (301; `['to' => …, 'status' => 302]`) | `1.x/basics/routing#redirects` |
| A form that emails | `config/forms.php` and `form:` in a content page's front matter | `1.x/features/forms` |
| Movement on hover or scroll (a card that lifts, a row that slides) | Tailwind's `motion-safe:` (`motion-safe:hover:-translate-y-1`); an effect a visitor switches on knowingly can keep moving with the class `motion-allowed` | `1.x/accessibility` |
| The site as plain files for a static host (no PHP on the server) | `php bin/console build:static` (output `deploy/`, Cloudflare Workers by default; `static` in `config/app.php`). Templates check `{% if not site.static %}`; a form works there with `'static' => ['from' => …]` (`'send' => 'smtp'` for your own mail server, `SMTP_*` in `.env`) and `form_action()`; `publish:static` puts it online | `1.x/static-site-generator` |
| Images | put them next to the Markdown and reference them (`![Alt](photo.jpg)`): responsive AVIF/WebP automatically; in Twig, `image()` | `1.x/features/images` |
| A reusable block inside Markdown | a content component: `::name{key="value"}` on its own line renders `templates/_components/name.twig` | `1.x/content/components` |
| UI texts in several languages | `{{ 'Text'|t }}` and `translations/<code>.php` | `1.x/features/languages` |
| A console command or deploy step | `src/Command/`, `$app->addDeployStep()` in `config/bootstrap.php` | `1.x/extending/commands` |

Templates **query** content; nothing is injected: `pages()`, `posts()`, `collection('name')`, with
`.where()`, `.orderBy()`, `.limit()`, `.one()`, `.all()`; paths inside a field
(`.where('data.featured', true)`, `.orderBy('data.date desc')`); `.except(item)` for "all but this
one" and `.neighbours(item, wrap: true)` for previous / next. Posts, pages and collection items have
`reading_minutes` (see `1.x/basics/querying`).

## Rules that trip people up

- **A route and a content page can't share a URL.** Don't add an `/about` route while
  `content/pages/about/` exists; `deploy` refuses.
- **Front matter keys are strict:** an unknown key is an error naming the file. Put custom values
  under `data:`. Dates are `YYYY-MM-DD` and must be real days.
- **A Datastar partial that queues an event sends only its events.** If it calls `patch_signals`
  and must also update HTML, wrap the HTML in `{% apply patch_elements %}…{% endapply %}`.
- **Accessibility is checked on every page, in both themes** (`tests/e2e/axe.spec.js`, WCAG 2.2 AA),
  and a failure stops CI. After changing templates or CSS, run `npm run build && npm run test:a11y`.
  Fix colours at the token in `resources/css/app.css`, in both themes; a button's accessible name
  starts with its visible text. Accept a problem only in the test's `EXCEPTIONS`, with a reason.
  See `1.x/accessibility`.
- **ARIA states are strings:** `data-attr` writes a boolean as a bare attribute or removes it, so use
  `data-attr:aria-expanded="String($open)"`, not `="$open"`.
- **Read signals with their type:** `signal('q')`, `signal_int('page', 1, min: 1, max: 1000)`,
  `signal_bool()`, `signal_enum()` in templates, `$this->signals()->int(…)` in controllers. Not
  `signals.page ?? 1`: a browser can send anything there.
- **Datastar actions are bound to their method:** a `datastar.post()` URL answers only POST.
  Changes that need authorization belong in a controller, not a template action.
- **Pages need assets:** run `npm run dev` (with `APP_DEBUG=1`) or `npm run build`; without either,
  pages fail with "Vite manifest not found".
- **A static build has no server:** template actions (`datastar.get('_partials/…')`), POSTs to
  routes and forms posting to the site stop `build:static` with a list. Wrap them in
  `{% if not site.static %}`, give the form a `static` sender, or load a page of the site with a
  plain GET (`datastar.action('get', path('blog_search'))`, built as a file) and filter it with
  `data-show`, as the blog search does. Browser-only Datastar keeps working.
- **`content()` can be stored, `block()` can't:** `{% set body = content(post) %}{{ body }}` prints
  HTML, but a `{% set x = block('name') %}` is plain text, escaped when printed: use `|raw` there.
- **`publish:static` needs a Cloudflare API token with two policies:** Workers Scripts: Edit and
  Account Settings: Read on the *account*, Workers Routes: Edit on the *domain*. Workers permissions
  under a domain-only policy don't count ("No access to the specified resource"). Passwords in
  `.env` go in single quotes: unquoted or double-quoted, `$NAME` is read as a variable. A static
  form that answers "wasn't sent": `npx wrangler tail --env-file ../.env` in `deploy/` shows why.
  More in `1.x/static-site-generator/troubleshooting`.
- **Pages are public and cacheable** (ETag, optional CDN). Never render visitor-specific data in a
  page; if a response must, call `$this->app->noStore()` in its controller (`no-store, private`).
  A response that sets a cookie gets that by itself.
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
CHROMIUM_PATH=/usr/bin/chromium npm run test:a11y  # accessibility only: every page, both themes (axe)
php bin/console check                     # is the server and the site ready to deploy? (read-only)
php bin/console deploy                    # production: check, build aside, then switch (see the deploy docs)
php bin/console deploy --rollback         # the previous build back
php bin/console build:static              # or: the site as plain files in deploy/, for a static host
php bin/console preview:static            # that build, served as its host will (:4173)
php bin/console publish:static            # build and publish it on Cloudflare (API token in .env)
php bin/console cache:clear               # back to development after a local deploy
```

With DDEV, prefix commands with `ddev` (`ddev composer test`, `ddev npm run dev`).

## Before you finish

- `composer test`, `composer analyse` and the browser tests pass. The skeleton's tests check its
  starter content: when you change a starter page, update its tests (the list is in
  `1.x/getting-started/checklist`); the examples go all at once with `site:clean`.
- Changed content or templates show in development right away; in production they need
  `php bin/console deploy`. `php bin/console check` finds what would stop it (a page's missing
  template, an entry point not in the Vite build) without changing anything.
- No secrets in templates, JavaScript or `public` config values: secrets live in `.env`.
