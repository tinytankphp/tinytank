# TinyTank roadmap

TinyTank is a starting point, not a full framework: SEO, Twig, Datastar, a Markdown blog, translations
and a deploy pipeline, with no database and no admin panel. A new site starts from this **skeleton**
(tinytankphp/tinytank, "Use this template") and installs the framework package (tinytankphp/framework,
`tinytank/framework`) with Composer; the docs are tinytankphp/docs. See CONTRIBUTING.md.

This roadmap lists what makes such sites easy to build and keep up to date. The first site will be
**Nocturne**, a calm ambient-sound mixer (claude.ai/design project "Nocturne Mockups"): a blog of
SEO articles around the app, plus a custom controller for the mixer on the home page. Its review
surfaced most of the generic needs below; Nocturne's own logic stays in its repository (section 6).

Status: `[ ]` to do · `[~]` in progress · `[x]` done

### Scope: where TinyTank stops
TinyTank is for sites whose content is files, edited by people comfortable with Markdown and git.
Content types stay few and concrete: the **blog** (dated articles), **content pages** (one-offs,
like Craft singles, nested like a structure) and **data collections** (repeating structured data,
like Craft channels). No configurable "section types". If a site needs an admin UI for editors, a
database, user accounts or permissions, relations between entries, revisions, or field layouts per
entry type, use Craft CMS (or similar) instead: rebuilding those here would only produce a worse CMS.

---

## 1. Before cloning

### [x] Automated tests
The framework holds a lot of logic that has only been checked by hand: routing with language
prefixes, stateless CSRF, the blog compiler and its validation rules, front matter inheritance,
translated posts, pagination, SEO / hreflang, translations, the deploy command.

- PHPUnit, driving the app through `Kernel::handle(Request::create(...))` (no web server needed)
- Fixture content (`tests/fixtures/content/blog/...`) covering every blog rule and error message
- One test per behaviour: language redirects, hidden untranslated posts, pagination edges,
  `/blog/page/1` redirect, 404s, CSRF accept/reject, ETag/304, Host-header independence,
  hreflang/sitemap/feed per language, Datastar partials (SSE output), asset serving
- PHPStan (high level); the code is already annotated
- GitHub Actions: tests + PHPStan + `npm run build` on every push

Why first: TinyTank is meant to be reused; tests are what make changing it safe.

Done: 133 tests (framework suite in the package's `tests/`, app suite in the skeleton's `tests/`), each checked by
deliberately breaking the behaviour it covers; PHPStan level 8 with no errors (level 9's strict
`mixed` rules mostly flag SPL iterators and console options); CI in `.github/workflows/ci.yml`.
See README "Testing".

### [x] Extension points
A site could add routes and controllers, but some things could only be changed by editing the
framework, which would have made later TinyTank updates conflict-prone:

- **Console commands**: auto-discover commands in `src/Command/` (today `bin/console` lists them by hand)
- **`config/bootstrap.php`**: a hook that receives the kernel, to register the app's Twig extensions,
  globals, functions and services
- **Deploy steps**: let the app add steps to `bin/console deploy` (e.g. "build audio" before "refresh
  Opcache"), configured rather than hardcoded

Rule of thumb for a site: the framework (`vendor/tinytank/framework`) is never edited; everything
app-specific lives in `src/`, `config/`, `templates/`, `resources/`, `content/`, `translations/`.

Done: `src/Command/` auto-discovery (`AppCommand` with a lazily booted kernel), `config/bootstrap.php`,
a PSR-11 container (`$app->container`, `$this->get()`), named deploy steps (`addDeployStep()`,
`--skip`, `--list-steps`), and TinyTank's Vite logic moved into a framework plugin
(`resources/vite/tinytank.js` in the package) so `vite.config.js` stays app-only. See README "Building a site on TinyTank".

---

### [x] Separate framework package
The framework moved out of `lib/` into its own repository (tinytankphp/framework, with its
history), installed by sites as a Composer VCS package. Maintainers clone it into the gitignored
`packages/tinytank`, which a path repository prefers over GitHub. Framework updates reach sites as
`composer update tinytank/framework` instead of git merges.

### [x] Documentation site
VitePress site in its own repository (tinytankphp/docs), versioned under `/1.x/`:
introduction, getting started, basics, content, features, extending, deployment, testing, security
and a reference section. Published to GitHub Pages by the docs repository's own workflow.
Keep it in step with the code: maintainers clone it into the gitignored `docs/` and update it with each change.

---

## 2. Generic features for small apps

### [x] Page-specific JavaScript and browser state
- **Several Vite entry points**: one bundle per page or feature (`resources/js/pages/*.js`), included
  through a Twig block, so an app's heavy JS never loads on blog pages (today there's one `app.js`)
- **Documented pattern "JS module + Datastar signals"**: Datastar drives the UI; a plain JS module
  does the heavy work (audio, canvas, maps…) and reads/writes signals
- **Signals persisted in `localStorage`**: settings, favourites, last state. Datastar v1 appears to
  ship persistence only in its Pro version, so this needs a small helper (verify first)
- **Public config for JS**: pass config values to the browser through `<script type="application/json">`
  from an explicit allowlist only, so secrets can't leak into the frontend by accident

Done: every `resources/js/pages/*.js` is an entry point (no config change), added by a page with
`{% set page_scripts = [...] %}`; the Datastar client moved into the framework (alias `'datastar'`,
one shared instance across bundles); framework helpers `ready`, `persist()` and `publicConfig()`
(alias `'tinytank'`); `public` allowlist in `config/app.php` printed by `{{ public_config() }}`,
refused if a value contains APP_SECRET. Verified: Datastar v1.0.2 has no persistence, and modules
run before Datastar applies `data-signals` (hence `ready`). Demo: a Web Audio tone on the home page.
Docs: "JavaScript & Datastar".

### [x] Dark / light / system theme
Follow `prefers-color-scheme` with a user override, applied before first paint by a tiny inline
script (no flash). Colour tokens in Tailwind's `@theme`; `dark:` variant driven by a `data-theme`
attribute.

Done: `{{ theme_script() }}` first in `<head>` (framework, `TinyTank\Theme`, with `Theme::hash()` for
the CSP); `theme()` in the 'tinytank' module (saves the `_theme` signal, follows system changes);
colour tokens with `light-dark()` in `resources/css/app.css`, so the system setting works even without
JavaScript; a radio-group toggle in the header; templates converted to tokens. Also: `cache:clear`
now rebuilds the normal Composer autoloader after a deploy. Docs: "Dark & light theme".

### [x] Self-hosted fonts and icons
Fonts bundled through Vite (e.g. `@fontsource`) instead of Google Fonts: no third-party requests
(privacy/GDPR), works offline, compatible with a strict CSP. Icon fonts (Material Symbols is ~3 MB)
subset to the icons actually used.

Done: Inter from Fontsource bundled by Vite (hashed files in public/build, only the scripts a page
uses are downloaded) and preloaded with the new `{{ vite_preload() }}`; icons through the Iconify
Tailwind plugin (`icon-[lucide--sun]`, only used icons are built, any Iconify set incl. Material
Symbols) plus custom SVGs in `resources/icons/` (`icon-[app--name]`; Vite restarts when they change).
Fixed on the way: Vite's `base` is now `/build/` for builds, so files referenced from CSS resolve.
Docs: "Fonts & icons".

### [~] Accessibility basics and UI building blocks
UI components (dialog / bottom sheet, toast, slider) are mostly design, so they don't go in the
framework: it would have to assume the site's Tailwind setup and tokens, change them under sites on
`composer update`, and steer every project toward them. Native HTML already does most of the work
(`<dialog>`, `<input type="range">`), with Datastar for the wiring.

1. [x] **Skeleton, now:** accessibility basics every site keeps whatever its UI: a skip link,
   visible focus styles, `prefers-reduced-motion` handling. Done, with `tests/e2e/accessibility.spec.js`
   (mutation-checked) and a docs section in Frontend & Vite.
2. [ ] **Dialog, toast, slider:** built in Nocturne, where they're needed.
3. [ ] **If a second project wants them:** extract into an optional package (`tinytank/ui`) with the
   package convention (`addTemplates()`, overridable components, one line in `bootstrap.php`).
4. [ ] **Docs:** a "Patterns" page with recipes to copy (native `<dialog>`, a toast with Datastar).
5. [ ] **Automated accessibility checks:** axe-core (`@axe-core/playwright`) on every sitemap page in
   the browser tests, failing on violations such as low colour contrast, missing labels or broken
   landmarks. Likely to flag a few of the lighter text colours (`text-subtle`) first.

### [x] Content Security Policy, cache-friendly
The main missing security header. Use **hashes** of the few inline scripts instead of per-request
nonces: a nonce would make every response different and break ETags, public caching and the static
export. Datastar needs `'unsafe-eval'`; the rest stays locked down, with extra hosts from config.

Done: `TinyTank\Csp` (`$app->csp`) sends a strict policy on HTML responses: `default-src 'self'`,
`script-src 'self' 'unsafe-eval'` + the theme script's hash, `img-src 'self' data:` (icon masks),
`style-src-attr 'unsafe-inline'` (data-show's `display: none`), `object-src 'none'`, `base-uri`,
`form-action`, `frame-ancestors 'self'`. Extra sources per directive in `config/app.php`
(`csp.sources`) or `$app->csp->allow()`; `report_only` mode; `allowScript()` for `execute_script()`
(removed from the clock demo); the Vite dev server is allowed while it runs. Verified in Chromium:
every demo and page works with no violations, in production and with the dev server; without `data:`
the icons break and without `'unsafe-eval'` Datastar does. Docs: Security → Content Security Policy.

### [x] Data collections
Structured content beyond blog posts (catalogues, FAQs, team, products…): `content/<collection>/`
with YAML or Markdown front matter, compiled into `var/cache`, validated loudly, translatable like
posts, usable from Twig and exportable as JSON for JS modules.

Done: `config/collections.php` defines each collection's typed fields (string, int, float, bool, date,
url, markdown, list, array; `?` optional), `sort`, `fallback` and a `json` allowlist; items are
`content/<name>/<slug>.md` (front matter + body → `html`) or `.yaml`, translations `<slug>.<lang>.md`
inheriting omitted fields. Compiled into `var/cache/collections.php` (deploy step `collections`);
`collections.faq` in Twig with `all`/`find`/`where`, `$app->collections` in PHP; JSON at
`/data/<name>.json` (per language, ETag). Loud errors for unknown/missing/mistyped fields, stray
files and folders, orphan translations, relative links. Demo: the FAQ on the home page; the app's
tests compile the real content/. Docs: Content → Data collections.

### [x] Translated slugs
A translation can set its own URL segment with `slug:` in its front matter: `content/pages/about/
index.it.md` with `slug: chi-siamo` is `/it/chi-siamo`, nested pages combine their parents'
translated slugs (`/it/chi-siamo/riconoscimenti`), posts likewise (`/it/blog/ciao-tinytank`). The
folder name stays the identity (`path` / `slug`), used by queries and by `path('page', {path: 'about'})`,
which writes the right URL for each language; items get a `uri` field with their URL in that
language. The untranslated URL redirects (301) to the translated one; duplicate URLs in a language
fail loudly, and `deploy` checks translated URLs against the routes too.

Done as described, for pages and posts. `Kernel::path()` localizes the `page` and `blog_post` routes;
the skeleton's controllers look items up by `uri` and redirect folder paths. Demo: /it/chi-siamo,
/it/chi-siamo/riconoscimenti, /it/blog/ciao-tinytank.

### [x] Content components
Small reusable pieces inside Markdown (posts, pages, collection bodies), like Hugo shortcodes:

```md
::audio-player{playlist="my_playlist"}
::youtube{id="dQw4w9WgXcQ"}
```

A `::name{key="value"}` line renders `_components/<name>.twig` with its arguments (plain strings,
numbers, booleans; no expressions) plus the item it appears in as `entry`. Content stays compiled:
items keep placeholders and `{{ content(item) }}` renders the components per request. An unknown
component fails the build with the file name; code blocks are never touched. Not raw Twig in
Markdown: that would turn content into code, break code examples and make every post a template.
Template lookup: the site's `templates/`, then packages (`$app->addTemplates($dir)`), then the
framework's defaults (`@tinytank/…`), so a site can override any component. The feed shows a link
to the post in place of a component.

Done: a CommonMark block extension (`TinyTank\Content`), items carry `components`, `content(item)` in
Twig and PHP, `addTemplates()`, `Kernel::TEMPLATES`; the deploy compiles every template folder.
Unknown, malformed or misplaced components (in a `markdown` field) fail with the file name; up to
three spaces of indentation like any block. Demo: `related-posts` at the end of the first post, the
syntax in the Markdown cheatsheet. Docs: Content → Content components.

### [x] Media embeds in posts
YouTube / Vimeo as framework default components (`::youtube{id="…"}`), overridable by the site.
**Click-to-load** for privacy: a self-hosted thumbnail and play button, and nothing reaches the
video host until the visitor clicks (a normal iframe contacts it on page load, even on
youtube-nocookie.com). The player host is allowed in `frame-src` only on pages that show a video.

Done: framework components `_components/youtube.twig` / `vimeo.twig` sharing `_embeds/video.twig`;
`TinyTank\Content\Embeds` validates ids at compile time and downloads posters and titles (deploy step
`embeds`; lazily once in development; only real images are saved; failures are remembered and
retried by deploy). Click-to-load with Datastar (the iframe has no src until play), a link without
JavaScript. New `csp_allow()` for per-response CSP sources, used by `video()`. Verified in Chromium:
before play only this site is contacted. Demo in the Markdown cheatsheet (Big Buck Bunny, CC BY);
the Privacy page mentions it.

### [x] Writing a TinyTank package (docs)
No plugin system: a Composer package exposes `register(Kernel $app)`, called from the site's
`config/bootstrap.php`, and adds Twig extensions, services, commands, deploy steps and templates
(`addTemplates()`, overridable by the site). Revisit only if real packages outgrow the convention.
Done: Extending → Writing a package (commands: an empty subclass in the site's src/Command/, the
package naming it in configure(), since attributes aren't inherited).

### [x] Content query API
Content is queried, not injected, as in Craft's element queries: templates and controllers ask for
what they need. One immutable, chainable query for every content type:

```twig
{% set latest = posts().tag('php').limit(5).all() %}
{% set result = posts().search(q).paginate(page) %}
{% set faq = collection('faq').all() %}
{% set ada = collection('team').slug('ada').one() %}
```

`where`, `slug`, `search`, `tag`, `language`, `orderBy`, `limit`, `offset`; `all`, `one`, `count`,
`exists`, `paginate`, `countBy`. Same in PHP (`$app->posts()`, `$app->collection('faq')`). The
`blog` and `collections` Twig globals go away: templates get context (`site`, `seo`, `datastar`)
automatically, content only when they ask. Designed so `posts('news')` can be added later.

Done: `TinyTank\Query` (immutable, generic for PHPStan: `posts()` is a `Query<Post>`), returned by
`posts()` / `collection(name)` in Twig and `$app->posts()` / `$app->collection(name)` in PHP; field
names are checked (a typo throws). `Blog` and `Collections` are now sources (compile, cache, files):
their old query methods and the two globals are gone; the skeleton's templates, BlogController,
sitemap and feed use queries. `paginate()` returns `items` and `per_page`. Docs: Basics → Querying
content; the scope rule is in Philosophy.

### [x] Markdown content pages (singles)
`content/pages/<path>/index.md` (+ `index.<code>.md`, images) for About, Privacy, Imprint, Contact…,
reusing the blog machinery (front matter, assets, translations, SEO, sitemap). Nested folders give
nested URLs (`content/pages/about/team/` → `/about/team`). An optional `template:` in the front
matter renders a page with its own Twig template (a contact page: text from Markdown, the form in
the template, a POST route for it). Queried with `pages()` in the same API.

Done: `TinyTank\Pages` compiles `content/pages/<path>/index.md` (+ translations inheriting image,
template, order, updated and `data`; files next to the page at `/media/pages/<path>/`), with
`path`, `parent`, `depth` for menus and trees, an optional `template:` and a free-form `data:`
mapping. `pages()` in the query API; the skeleton's `PageController` and a catch-all route with the
new route `priority` (-1), so other routes always win; `deploy` refuses pages hidden behind a route.
PageSeo (WebPage JSON-LD), sitemap entries with `lastmod`, `cache:clear` clears published page
files. Front matter YAML errors now name the file (posts too). Demo: Privacy, About (own template
listing its children) and About → Credits, plus a footer menu. Docs: Content → Content pages.

### [x] Small helpers
- [x] `$this->json($data)` in controllers for app data endpoints
- [x] `KernelTestCase` skips Vite when there's no manifest (or fails with a clear message): today a
  new site running `composer test` before the first `npm run build` gets 11 failures that only say
  "Something went wrong." (CI is fine because it builds first)
- [x] `MEDIA_URL` setting so post images and other large files can be served from a CDN, with long cache
  headers by default

Done: `KernelTestCase` lets pages render without a Vite build (a comment instead of the tags) and
`requireViteBuild()` skips asset tests with a clear message; verified by running the skeleton's tests
with public/build removed (all pass, 2 skipped). `MEDIA_URL` (`media_url`) serves post and page files
and video posters from a pull CDN, adds its host to img-src/media-src, refuses non-https values; the
server docs cache /media/ for a week (/build/ stays a year, immutable).

### [ ] Optional PWA support
Web app manifest generated from config (name, colours, icons) and an optional service worker
(e.g. `vite-plugin-pwa`), switched on per project.
Like the UI blocks: opt-in per project, so an optional package rather than the framework.

### [x] Frontend tests
Vitest for JS modules; Playwright (Chromium is already in the DDEV container) for real user flows.
Wired into the same CI as the PHP tests.

Done: Vitest in the framework (`tests/js`, jsdom + the real Datastar): persist, ready, theme,
publicConfig; in its CI. Playwright in the skeleton (`tests/e2e`, PHP's built-in server in
production mode): every sitemap URL, Datastar demos, theme without flash, persistence, click-to-load
video, translated slugs, no-JavaScript fallbacks; every test fails on a CSP violation or console
error. In CI with an HTML report on failure. Both suites mutation-checked. The skeleton got a
favicon (the missing one logged a 404 on every page).

---

## 3. Stabilization (after the external review of 2026-10-06)

An external review (kept locally, not in the repository) checked the framework at `917b076`: all
suites passing, and its probes reproduced twelve problems at the edges (extension order, deploy
failures, odd input, docs promising more than the code). All twelve were verified against the code.
Its advice, which we share: sharpen what exists rather than grow. Batches in the agreed order:

### [x] 3.1 Hardening: small bugs, each with a regression test
- [x] **Signed Datastar actions bound to their method**: a `datastar.post()` URL also works with GET
      today (the signature covers template and variables only, CSRF applies to unsafe methods). Sign
      the method, refuse others (HEAD allowed for GET); document that mutations belong in controllers
      and signed variables are readable and replayable
- [x] **Custom spam checks registered as documented**: `Forms` validates check names in its
      constructor, before `config/bootstrap.php` can call `addSpamCheck()`. Validate after bootstrap
      (a misspelled check still fails at boot); test it the way a package uses it
- [x] **Pagination overflow**: `/blog/page/<huge number>` is a 500 (`($page - 1) * $perPage` becomes
      a float). Clamp before multiplying; limit the route's digits
- [x] **Strict dates**: `2026-02-30` is accepted as `2026-03-02`; the parsed date must format back to
      the same string
- [x] **Datastar event queue reset per request**: a template failing halfway leaves its queued events
      for the next request on the same kernel (tests, workers)
- [x] **`persist()` reads `globalThis.localStorage` outside its `try`** (as a default parameter): in
      Safari with blocked storage or a sandboxed iframe, merely reading it throws
      Done: the method is signed into Datastar configs (`m`), other methods get a 405, configs from
      before are refused (400); forms are validated after bootstrap (fixture spam check `word`); dates
      are checked before YAML reads them (`TinyTank\Content\StrictYaml`, also for collections, and
      always Symfony YAML now); the skeleton's blog page route takes at most 6 digits.

### [x] 3.2 License and release basics
- [x] **License**: no `LICENSE` file or Composer `license` field in the framework or the skeleton yet,
      so nobody else may legally use them. Choose (MIT is usual: Symfony, Slim, Datastar)
- [x] **First tagged release** (`v0.1.0` or `v1.0.0-beta.1`), `CHANGELOG.md`, upgrade notes when the
      skeleton changes; Packagist optional
- [x] **`SECURITY.md`**: private vulnerability reporting (GitHub's is enough)
- [x] **Composer `require`**: `ext-intl`, `ext-mbstring`, `ext-xmlwriter` (sitemap, feed); GD or Imagick
      in `suggest` (Composer can't say "one of") and in `check`
- [x] **Dependabot** (Composer, npm, GitHub Actions): weekly advisory and update PRs
      Done: MIT, Copyright (c) 2026 lab909, in all three repositories (sites replace the skeleton's
      `LICENSE` and `SECURITY.md` with their own); `CHANGELOG.md` and `SECURITY.md` in the framework;
      sites require `^0.1@dev` with `prefer-stable` (latest tag), the skeleton's CI tests against the
      framework's `main`; `dev-main` aliased `0.1.x-dev`; release steps in CONTRIBUTING.md; docs stay
      at `/1.x/` ("0.x beta" in the version menu). First tag: `v0.1.0`.
      **Renamed from Starlite to TinyTank** (a Python framework and a PHP microframework had
      confusable names): GitHub org `tinytankphp`, Packagist `tinytank/framework` and
      `tinytank/tinytank` (the skeleton, also for `composer create-project`), namespace `TinyTank\`.

### [ ] 3.3 Caching boundary
- [ ] A response with **`Set-Cookie`** (or one marked private by its handler) gets `private, no-store`,
      never `public`, CDN or not; `$app->noStore()` as the public version of what forms use. This is
      the "pages that read the session are private" rule, decided before any login feature
- [ ] Docs: `cdn->skip()` means "not at the CDN" (still `public, no-cache` for browsers), `noStore()`
      means "nowhere"; tests with CDN caching on
- [ ] Possibly extract the response policy (caching, CSP, headers) from the Kernel while there, and
      replace the bound-closure read of Symfony's cache-control state with an explicit marker

### [ ] 3.4 Deploy safety and `bin/console check`
- [ ] **Build first, switch last**: `deploy` empties `var/cache` before validating anything, so a
      failure halfway leaves the live site half-built. Compile into `var/cache.next/` (and publish media
      to a `.next` folder), validate, then swap; keep `.prev` for `deploy --rollback`. Acceptance: a
      failing step leaves the old site intact
- [ ] **Runtime state out of the cache**: form rate limits live in `var/cache/forms/`, so every deploy
      resets them. Move them (and the alert dedup file) to `var/state/`, kept across deploys
- [ ] **Missing Vite manifest fails the deploy** (pages need it), with an opt-out for sites without Vite
- [ ] **`bin/console check`**, read-only preflight, also run first by `deploy`: PHP version and
      extensions, image driver and formats, `APP_SECRET`/`APP_URL`, writable `var/`, content compiles,
      **page templates and forms exist** (a page with a missing `template:` deploys today, then 500s),
      components, the Vite manifest and its entries, shadowed pages; `--json` for CI
- [ ] **Images loaded lazily**: without GD or Imagick the site doesn't boot at all today, even pages
      without images (`Images` probes the driver in its constructor)
- [ ] **Boot errors handled**: a minimal `try/catch` in `public/index.php` (plain 500 and PHP's log in
      production, rethrow in debug), since errors during `boot()` bypass the framework's handler
- [ ] Docs: a release-directory recipe (Deployer, symlinked `current/`) for servers where you control
      the web root

### [ ] 3.5 Small site features and docs
- [ ] **Redirect map** (`config/redirects.php`, old path => new path, 301), validated at deploy
- [~] ~~**`noindex` / `sitemap: false`** in page front matter~~: done as `unlisted: true` (noindex, not
      in the sitemap, skipped by menus), for the lab909 style guide; a route option to stay out of the
      sitemap is still open
- [ ] **Typed signal helpers**: `signal('q', '')`, `signalInt('page', 1, max: 1000)`,
      `signalEnum('sort', […])`; malformed JSON → 400, oversized body → 413
- [ ] **Docs accuracy**: "one-time token" → signed timestamp token (age, not single use); "cheap 304"
      saves transfer, the handler still runs; what the log's defaults leave out vs. what your own
      messages may contain; the controllers guide's Blog methods (`find()`, `page()`, `search()`,
      `tags()` no longer exist); `composer test` runs the app's suite only
- [ ] **SVG in `/media`**: a server snippet serving them with a script-blocking CSP, as development does
- [ ] **Health check recipe** (docs): a route answering 200 when the compiled cache is readable
- [ ] Docs: PHP-FPM (one kernel per request) is the supported model; worker servers aren't (yet)

### Deferred until there's evidence
Compiled media manifest (dimensions, variants, posters); content-hashed media URLs; query indexes and
precomputed search text; request/response hooks (when Nocturne or an API needs them); CI matrix
(PHP 8.5, GD + Imagick, lowest dependencies) before a 1.0; durable mail outbox (document that delivery
is synchronous); worker servers.

### Declined, with reasons
- **Video, CDN purgers and email alerts as separate packages**: they cost nothing when unused (off by
  default, or only run when a post uses them); splitting adds releases and docs for no runtime gain.
  The image driver is the real case, fixed by lazy loading
- **PSR-7/PSR-15 middleware**: HttpFoundation is deliberate; a bridge only for a real integration
- **Formal pilot programme and benchmarks**: Nocturne is the pilot

---

## 4. Nice to have

- [x] **Translate the site name and description** (`config/app.php` → `site`): each value is a string
      or a map language => value (name, description, share image, author); `$site->name()` etc. in
      PHP, `site.name` in Twig, in the current language; missing languages use the default one's.
- [x] **Forms and a contact form** (agreed design):
      - **Framework:** forms defined in `config/forms.php` (typed fields, required/min/max/choices,
        custom rules per field from `bootstrap.php`), validation messages through the translations,
        sending with Symfony Mailer (`MAILER_DSN` / `MAILER_FROM` in `.env`, the visitor in Reply-To),
        a `SpamCheck` interface with local checks only: honeypot, timing (a signed token; pages with a
        form aren't cached), rate limit (a keyed hash of the IP, never the IP), link limit.
      - **Skeleton:** a Contact content page (`form: contact` in its front matter), works without
        JavaScript, Datastar for inline errors and the thank-you; DDEV's Mailpit in development; a
        note on the Privacy page. Submissions are emailed, never stored.
      - **Later:** ALTCHA (self-hosted proof of work) as an opt-in check; third-party captchas
        (Turnstile, hCaptcha) only as optional packages, loaded on interaction, CSP on that page only.
      - **Not now:** attachments.
      - Done: `TinyTank\Forms` (Form, Forms, Submission, Spam\{Honeypot, Timing, MaxLinks, RateLimit}),
        `form:` in page front matter, `form_spam()`, `KernelTestCase::request(parameters:)`; the skeleton's
        Contact page (`/contact`, `/it/contatti`) checked end to end with Mailpit, plus PHPUnit and
        Playwright tests (with and without JavaScript); docs: Features → Forms.
- [x] **Logging**: Monolog (PSR-3, `$app->logger`, `LoggerInterface` in the container) instead of bare
      `error_log()`. A file per day in `var/log/`, deleted after 14 days (`log.days`); `LOG_LEVEL`
      (debug in development, info in production); or a stream (`php://stderr`). **Privacy:** entries
      carry method, path and route only (no IP, browser, referrer or query string), 404s aren't logged,
      traces have no function arguments in production (`zend.exception_ignore_args`), no form content,
      no third-party service. Email alerts (`LOG_ALERT_TO`, through the forms' mailer) for errors, with
      the request's earlier lines (fingers crossed), once per hour per error (deduplication), sent after
      the response; a failed alert is noted in the file. PHP warnings and fatal errors in production go
      through the logger (deprecations are left to PHP). `KernelTestCase::logged()`. Docs: Features →
      Logging (with the reasons), Servers → Access logs.
- [x] **CDN caching** (opt-in, `CDN_CACHE=1`): pages get `s-maxage`, `stale-while-revalidate` and
      `stale-if-error` (the CDN keeps serving them while the site is down); every route except
      `cdn.exclude`, the Datastar endpoint, Datastar requests, content pages with `cdn: false`, pages with
      a form, errors, and responses with their own `Cache-Control` (which now always wins). `ttl` an hour
      when the CDN can be purged, 5 minutes otherwise. Purging: a `Purger` interface with Cloudflare,
      Bunny and a shell command built in (`CDN_PURGE`), others from packages (`$app->cdn->usePurger()`);
      `deploy` purges everything last; `bin/console cdn:purge blog/my-post | https://… | --all`.
- [ ] **Static export** (to evaluate later; CDN caching gives most of the resilience first): render
      pages to HTML at deploy, served by nginx/Apache with `try_files`, PHP behind for forms and
      Datastar. Worth it for a cheap server without a CDN. Ideas so far: hybrid, opt-in **per route**
      (`static.routes`); pages found through the sitemap plus their links; only 200, public, not
      `no-store`; CSP as a `<meta>` tag (per page), the fixed security headers in the server config;
      write only changed files, swap the folder in at once, `.gz` copies; `cache:clear` removes them.
      Follow-up idea: the forms' timing token fetched through Datastar, so form pages can be static too
- [ ] **Modal images** (lightbox): click an image in a post or page to see it large, in a modal over
      the page. A native `<dialog>` (focus trap, Esc and backdrop to close, focus back on the image),
      opened with Datastar or a small helper, loading a larger version from the image's `srcset`; works
      as a plain link to the full image without JavaScript. Opt-in per image or per page; captions
      from the alt text or title; arrows/swipe between a post's images as a possible second step.
      Respect `prefers-reduced-motion`; no third-party library, nothing leaves the site
- [x] **Responsive images**: AVIF (where supported) and WebP at 480/960/1440 px with `srcset`, `width`
      and `height`, lazy loading, for Markdown images and `image()` in templates; built at deploy (kept in
      `var/images/`, reused), on first request in development. **Every published image loses its
      metadata** (GPS position, device, time) and is turned upright. Intervention Image v4 (GD or
      Imagick). Presets like Craft's transforms (`images.presets`: widths, sizes, ratio, mode crop/fit/
      letterbox/stretch, position, background; every option defaulted), `{preset: 'card'}` in `image()`;
      only configured widths and shapes can be requested. Docs: Features → Images.
- [ ] **Translated route segments** (`/it/articoli` instead of `/it/blog`): Symfony's localized
      routes, one path per language, defined in `config/routes.php`.
- [ ] **A second blog-like section** (e.g. `content/news/` beside `content/blog/`): another instance
      of the blog machinery, queried as `posts('news')`. Only when a real site needs it.

---

## 5. The lab909 site: the first field test

[lab909.dev](https://lab909.dev) (`lab909.io` redirects to it), the website of lab909, listing its
projects (open source and paid). The first real TinyTank site, built in this workspace in
`sites/lab909` (its own private repository, lab909/lab909-website), so framework fixes and missing
features can be handled as they come up. Created the way users will: `composer create-project
tinytank/tinytank`, framework v0.1.0 from Packagist. (A separate TinyTank website was dropped: the
docs at docs.tinytank.dev work well as the landing page; `tinytank.dev` redirects there.)

- [x] Create the site, its identity, `.env`, git; local at https://lab909.ddev.site (DDEV hostname
      and nginx block, local only)
- [x] **Unlisted content pages** (`unlisted: true`: noindex, not in the sitemap, skipped by menus),
      instead of separate `noindex` and `sitemap: false` switches (batch 3.5, pulled forward)
- [x] Hidden **style guide** page with every content element, for the designer (`/style-guide`,
      unlisted): it found the image bug below on its first render
- [x] **Syntax highlighting** for code blocks, at compile time (tempest/highlight), asked for while
      listing the content elements for the designer; styles in the skeleton's `app.css`
- [x] **Projects** (from the Claude Design handoff): the site's shell (Orbitron and JetBrains Mono
      self-hosted, the dark/light palette, analog effects kept per visitor with `persist()`), the
      `projects` collection (12 case files, Markdown bodies with highlighted code), `/projects` in pages
      of five, a first `/projects/<slug>`. Within ~430 pixels of the design in both themes
- [x] **Homepage**: the hero with SEQ-909, a working drum sequencer (Web Audio, synthesized 909-style
      voices, a lookahead scheduler; silent until the first tap; tempo 60–220, random patterns), the
      latest case files as cards, the latest blog posts, three featured sponsors. Projects and sponsor
      reviews share one article layout (the Blog Post design), ready for the blog
- [x] **Sponsors**: tool reviews with affiliate links, as child pages of `content/pages/sponsors/`
      (images next to the Markdown, `data.date`, `data.featured`), `rel="sponsored"` and a disclosure;
      Privacy, Cookies and Sponsors in the footer; social links from `config/globals.php`
- [ ] The other pages of the design: project page (full), blog, blog post, about, contact
- [ ] Hosting with PHP 8.4, HTTPS, deploy with `bin/console deploy`; possibly a CDN
- [ ] Release the fixes found here as 0.1.x

**Friction log** (fix in TinyTank, then tick):
- [x] **No place for site-wide values** (social links): added `config/globals.php` → `globals`
- [x] **Pages couldn't be filtered or sorted by `data`** (sponsors by date, the featured three):
      `data` wasn't a query field, and queries had no paths; now `where('data.featured', true)`
- [ ] **Collection items can't have their own images**, so the channels' images are plain files in
      `public/`, sized by hand, without AVIF/WebP versions. Responsive images for `public/` files, or
      collection folders?
- [x] **Reduced motion vs. an effect the visitor asked for:** the skeleton's reduced-motion rule
      (`!important` in `@layer base`) froze the VHS roll under the header; an override has to be in
      the same layer. Site-level, but worth a line in the accessibility docs
- [x] **The default theme was always the system's**; the design is dark first. Added
      `theme.default` (`'dark'`): the inline script and its CSP hash follow
- [x] **Templates couldn't tell the current page** for a menu's current section: added `site.path`
- [x] **Bug:** Vite inlined a tiny font subset as a `data:` URL, which the CSP blocks: the Vite plugin
      never inlines fonts now (a first Vitest test for the plugin)
- [x] **Accessibility trap:** `data-attr:aria-checked="$x"` writes `aria-checked=""`; ARIA needs
      `String($x)`. Caught by a browser test; now in the Datastar and theme docs and in AGENTS.md
- [x] `create-project` gives the site the template's identity: `composer.json` name `tinytank/tinytank`,
      description and MIT license; `package.json` name `tinytank`; TinyTank's `LICENSE` and
      `SECURITY.md`; the maintainer files `ROADMAP.md` and `CONTRIBUTING.md`. Fix: a
      `post-create-project-cmd` that resets the identity, removes maintainer files, copies `.env.example`
      to `.env` with a fresh `APP_SECRET`, and drops `/composer.lock` from `.gitignore`. Done:
      `bin/create-site.php` (also points CI at the lock file, adds Composer to Dependabot, writes the
      site's README), tested on a copy and with a real `create-project`
- [x] The DDEV project is named `tinytank` in every site (collisions); its config also exposes the
      docs port (maintainers only). Fix: the post-create script names it after the folder, drops the
      docs port. Done, in the same script
- [x] **Bug:** a Markdown image that isn't a photo of the folder (`/favicon.svg`, a URL, an SVG in
      the folder) crashed the page: TinyTank's image renderer never passed CommonMark's configuration
      to its fallback. Fixed with a regression test (0.1.2)
- [x] Working on the framework from a site (`packages/tinytank` symlink) swaps `vendor/tinytank/framework`
      from a folder to a symlink: PHP-FPM workers keep serving the old classes until a reload
      (`ddev restart`, or `kill -USR2` of the FPM master). Documented in CONTRIBUTING
- [x] Code block labels overlapped long lines that scroll: room above the code (skeleton CSS)
- [x] Demo content to remove by hand: listed, with the other first decisions, in the docs' new
      **Starting a site** checklist (identity, starter content and the style guide, contact and privacy,
      licenses), linked from the README and the script's output
- [x] The style guide joined the skeleton (`content/pages/style-guide/`), with a PHPUnit and a browser
      test: with the image bug reintroduced, both suites fail, so it would have been caught before 0.1.0

---

## 6. For the Nocturne repository (not TinyTank)

Kept here only as a reminder of what the Nocturne site will add on top of TinyTank:

- Spike first: background playback with the screen locked (iOS suspends Web Audio; `<audio loop>`
  leaves gaps) and gapless mixing; Media Session API for lock-screen controls. Test on real phones.
- Audio engine (layers, gains, fades, variant crossfades, previews), mixer UI (1a dense vs. 1b
  spacious), saving / My mixes in `localStorage`, share links
- `audio:build` command (ffmpeg: loudness normalisation, seamless loops, Opus + AAC, preview clips),
  registered through TinyTank's deploy steps; audio from a CDN via `MEDIA_URL`
- Sound catalogue as a data collection; one SEO page per sound (`/sounds/rain`); sound credits page
- Open questions: mixer direction, layers at once, music source and licensing, launch languages,
  analytics, anything server-side

---

## Suggested order

1. ~~Tests + PHPStan, extension points, docs, separate package~~ — done
2. ~~Page-specific JS + Datastar pattern, theme, fonts/icons, CSP (section 2)~~: what an app like
   Nocturne needs on day one
3. ~~Data collections, content query API, content pages, media embeds, helpers, frontend tests~~,
   UI building blocks, PWA
4. Stabilization (section 3), in its batch order, as the field tests show what matters
5. The lab909 site (section 5): the first field test
6. Create the Nocturne site from the skeleton; section 4 items as projects need them

---

## Housekeeping when starting a site

Moved to the docs: [Starting a site](https://docs.tinytank.dev/1.x/getting-started/checklist), and
`bin/create-site.php` does the mechanical part after `composer create-project`.
