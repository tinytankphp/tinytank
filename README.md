<p align="center">
  <a href="https://tinytank.dev/"><img src="https://docs.tinytank.dev/logo-full.svg" alt="TinyTank" width="240"></a>
</p>

# TinyTank

A tiny, database-free PHP micro framework for static-like dynamic sites: **Datastar** for
reactivity, **Symfony Routing**, **Twig**, a **Markdown blog** with translations, **SEO** (Open Graph,
JSON-LD, hreflang, sitemap, feeds) and **Vite + Tailwind CSS**. Requires PHP 8.4.

Everything a request needs is compiled ahead of time into plain PHP files in `var/cache`, which
Opcache keeps in shared memory: no parsing, no database queries, no file scanning per request.

TinyTank is freely inspired by [Craft CMS](https://craftcms.com) (Twig-first templates, content you
query rather than receive, image transforms) and [Datastar](https://data-star.dev) (hypermedia: the
server renders HTML, the browser stays light).

**This repository is the site skeleton: start every new site from it.** The framework itself is the
[`tinytank/framework`](https://github.com/tinytankphp/framework) package, installed by Composer.

🌐 **Website: https://tinytank.dev/** · 📖 **Documentation: https://docs.tinytank.dev/**

## Create a site

```sh
composer create-project tinytank/tinytank my-site
cd my-site && git init
ddev start
ddev npm install && ddev npm run dev     # https://my-site.ddev.site, with hot reload
```

`create-project` prepares the site for you: its own name, `.env` with a fresh secret, a tracked
`composer.lock`, CI on that lock file. It asks whether to keep TinyTank's examples (demos, sample
posts, an Italian translation) to learn from; `php bin/console site:clean` removes them later, with
their tests. (With GitHub's **Use this template** button instead: clone your new repository and run
`php bin/create-site.php` once.)

Then work through the [Starting a site](https://docs.tinytank.dev/1.x/getting-started/checklist)
checklist: your site's identity, the starter content (including the style guide at `/style-guide`),
contact and privacy. [Installation](https://docs.tinytank.dev/1.x/getting-started/installation)
covers the details and setups without DDEV.

## Your site, TinyTank's framework

Everything in this repository is yours to change: routes in `config/routes.php`, services and
deploy steps in `config/bootstrap.php`, code in `src/`, templates in `templates/`, posts in
`content/`. The framework lives in `vendor/tinytank/framework` and is never edited; update it with:

```sh
ddev composer update tinytank/framework
```

See [Building a site on TinyTank](https://docs.tinytank.dev/1.x/extending/).

## Common commands

```sh
ddev composer test           # PHPUnit (your app's tests)
ddev composer analyse        # PHPStan, level 8
ddev exec CHROMIUM_PATH=/usr/bin/chromium npm run test:e2e   # the site in a real browser (after npm run build)
ddev console deploy          # production build + Opcache refresh
ddev console cache:clear     # back to development after a deploy
```

## Licenses

TinyTank (this skeleton and the framework) is released under the [MIT License](LICENSE),
Copyright (c) 2026 lab909. **A site you build from it is yours:** replace `LICENSE` with your site's
own terms (or remove it), and `SECURITY.md` with your own contact. The MIT notice only has to stay
with copies of TinyTank's code itself, which `vendor/tinytank/framework` carries.

The site's built files include third-party code: the Datastar client (MIT), Lucide icons (ISC/MIT),
the Inter font (SIL Open Font License) and Tailwind CSS (MIT). They carry short notices, and
`public/third-party-licenses.txt` has the full texts. If you change fonts, icons or scripts, update
that file too (`tests/LicensesTest.php` checks it).

## Contributing to TinyTank

Maintainers work on the skeleton, the framework and the docs side by side: see
[CONTRIBUTING.md](CONTRIBUTING.md). The roadmap is in [ROADMAP.md](ROADMAP.md); the framework's
releases are in its [CHANGELOG](https://github.com/tinytankphp/framework/blob/main/CHANGELOG.md).
Security problems: see [SECURITY.md](SECURITY.md).
