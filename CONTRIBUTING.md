# Contributing to TinyTank

TinyTank is three repositories:

| Repository | What it is | In a site |
|---|---|---|
| [tinytankphp/tinytank](https://github.com/tinytankphp/tinytank) | this site skeleton | the starting point, via **Use this template** |
| [tinytankphp/framework](https://github.com/tinytankphp/framework) | the framework package, `tinytank/framework` | `vendor/tinytank/framework`, installed by Composer |
| [tinytankphp/docs](https://github.com/tinytankphp/docs) | the documentation (VitePress) | not included |

## Maintainer setup: all three side by side

Changes often span the framework, the skeleton and the docs. Work on them together by cloning the
other two repositories **inside** this one. Both locations are gitignored, so nothing about them
is ever published with the skeleton:

```sh
git clone git@github.com:tinytankphp/tinytank.git && cd tinytank
git clone git@github.com:tinytankphp/framework.git packages/tinytank
git clone git@github.com:tinytankphp/docs.git docs
ddev start
ddev composer update tinytank/framework     # now symlinks packages/tinytank instead of using Packagist
```

How it works: `composer.json` lists a path repository (`packages/*`), which Composer checks before
Packagist. When `packages/tinytank` exists, Composer uses it and symlinks it into
`vendor/tinytank/framework`, so framework edits take effect immediately. In a regular clone the
`packages/` folder is empty (only `.gitkeep`) and Composer installs the framework from Packagist.

Because the source differs, `composer.lock` is never committed in the skeleton.

Real sites built alongside TinyTank, to field-test it, can live in `sites/<name>/` (also gitignored),
each its own repository created with `composer create-project tinytank/tinytank sites/<name>`.
To try a framework change in such a site before it's released, link the clone into the site
(`ln -s ../../../packages/tinytank sites/<name>/packages/tinytank`, then `composer update
tinytank/framework` there), and don't commit the site's `composer.lock` while it points at the
clone. After switching `vendor/tinytank/framework` between a folder and a symlink, reload PHP-FPM
(`ddev restart`), or its workers keep serving the old classes for a while.

Each clone is its own git repository: commit and push framework changes from `packages/tinytank`,
docs changes from `docs/`, and skeleton changes from the root.

## Running the checks

```sh
# the skeleton (app tests)
ddev composer test && ddev composer analyse

# the skeleton in a real browser (Playwright, against the production build)
ddev npm run build && ddev exec CHROMIUM_PATH=/usr/bin/chromium npm run test:e2e

# the framework
cd packages/tinytank && composer update && composer test && composer analyse
cd packages/tinytank && npm install && npm test    # Vitest: the browser helpers

# the docs
cd docs && npm install && npm run build     # npm run dev → https://<project>.ddev.site:5174
```

Without `npm run build` in the root, the app tests that check built assets are skipped.

## Releasing

The framework is released with tags; sites require `^0.1@dev` and, with `prefer-stable`, get the
latest tag. The skeleton's CI tests against the framework's `main` branch instead.

1. In `packages/tinytank`: move the entries under **Unreleased** in `CHANGELOG.md` to a new version
   with today's date, including what sites must change. While TinyTank is 0.x, changes that need
   sites to update something go in a new minor version (0.2.0); fixes, and additions that work
   without any change in a site, in a patch (0.1.1).
2. All checks green (above), then commit and tag: `git tag -a v0.1.1 -m "TinyTank 0.1.1"`.
3. A new minor version: update `extra.branch-alias` (`"dev-main": "0.2.x-dev"`) in the framework's
   `composer.json`, the constraint in the skeleton's `composer.json` (`^0.2@dev`) and the docs
   (Updating TinyTank).
4. Push the framework with its tag first (`git push --follow-tags`). **Wait until Packagist has the
   new commit**, then push the skeleton, then the docs: the skeleton's CI installs the framework from
   Packagist, and would test the previous version. Check the metadata Composer reads, not the package
   page (cached for hours): `https://repo.packagist.org/p2/tinytank/framework~dev.json` (for `dev-main`)
   or `…/p2/tinytank/framework.json` (tags); its `reference` is the commit.
   Optionally, tag the skeleton with the same version, and create a GitHub release from the
   changelog entry.

## Guidelines

- Keep the framework free of `App\` code: anything a site might change belongs in the skeleton, or
  needs an extension point in the framework.
- Every behaviour change comes with a test in the framework or the app suite, and a docs update.
- Prefer proven libraries for infrastructure; TinyTank's own code is the glue between them.
- PHPStan level 8 must stay clean in both repositories.

## Suggesting docs changes

Every docs page has an **Edit this page on GitHub** link. Small fixes can go straight to the docs
repository as a pull request.
