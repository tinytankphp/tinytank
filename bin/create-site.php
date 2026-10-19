<?php

declare(strict_types=1);

/*
 * Turns a fresh copy of the TinyTank skeleton into your site. Composer runs it once, right after
 * `composer create-project tinytank/tinytank my-site` (post-create-project-cmd). If you started
 * from GitHub's "Use this template" button instead, run it yourself, once: `php bin/create-site.php`.
 *
 * It names everything after the site's folder (my-site), creates .env with a fresh APP_SECRET,
 * commits to composer.lock, removes the files that are about developing TinyTank itself, asks
 * whether to keep TinyTank's examples (demos, sample posts, the sample translation), and then
 * removes itself. What it can't decide for you is in the docs' "Starting a site" checklist.
 *
 * Without a terminal (CI, --no-interaction) it keeps the examples; choose up front with
 * --keep-examples or --remove-examples. `php bin/console site:clean` removes them any time later.
 */

$root = dirname(__DIR__);
$slug = trim((string) preg_replace('/[^a-z0-9]+/', '-', strtolower(basename($root))), '-') ?: 'my-site';
$done = [];

$read = static fn (string $file): ?string => is_file("{$root}/{$file}") ? (string) file_get_contents("{$root}/{$file}") : null;
$write = static function (string $file, string $contents) use ($root): void {
    file_put_contents("{$root}/{$file}", $contents);
};
$json = static fn (array $data): string => json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . "\n";

// composer.json: the site's own name, not TinyTank's; no open-source license until you choose one.
if (($composer = $read('composer.json')) !== null) {
    $data = json_decode($composer, true, flags: JSON_THROW_ON_ERROR);
    $data['name'] = "app/{$slug}";
    $data['description'] = "{$slug}: a website built with TinyTank";
    $data['license'] = 'proprietary';
    unset($data['scripts']['post-create-project-cmd']);
    $write('composer.json', $json($data));
    $done[] = "composer.json: app/{$slug}, proprietary";
}

// package.json and its lock file: the same name.
if (($package = $read('package.json')) !== null) {
    $data = json_decode($package, true, flags: JSON_THROW_ON_ERROR);
    $data['name'] = $slug;
    $write('package.json', json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . "\n");
}
if (($lock = $read('package-lock.json')) !== null) {
    $data = json_decode($lock, true, flags: JSON_THROW_ON_ERROR);
    $data['name'] = $slug;
    if (isset($data['packages'][''])) {
        $data['packages']['']['name'] = $slug;
    }
    $write('package-lock.json', json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . "\n");
}

// .env: from .env.example, with a fresh secret, the local DDEV address and debug mode on.
if ($read('.env') === null && ($example = $read('.env.example')) !== null) {
    $env = preg_replace(
        ['/^APP_SECRET=.*$/m', '/^APP_URL=.*$/m', '/^APP_DEBUG=.*$/m'],
        ['APP_SECRET=' . bin2hex(random_bytes(32)), "APP_URL=https://{$slug}.ddev.site", 'APP_DEBUG=1'],
        $example,
    );
    $write('.env', (string) $env);
    $done[] = ".env: a fresh APP_SECRET, APP_URL https://{$slug}.ddev.site, debug on";
}

// .gitignore: a site commits its composer.lock, so every server installs what you tested.
if (($gitignore = $read('.gitignore')) !== null) {
    $write('.gitignore', (string) preg_replace('/^# A new site commits its own lock file:.*\n\/composer\.lock\n/m', '', $gitignore));
    $done[] = '.gitignore: composer.lock is committed';
}

// DDEV: the project is named after the folder (two sites can run side by side), without the port of
// TinyTank's own docs.
if (($ddev = $read('.ddev/config.yaml')) !== null) {
    $ddev = (string) preg_replace('/^name: .*$/m', "name: {$slug}", $ddev, 1);
    $ddev = str_replace('tinytank.ddev.site', "{$slug}.ddev.site", $ddev);
    $ddev = (string) preg_replace('/^ *# Documentation \(cd docs.*\n(?: {4,}.*\n|    - name: docs\n)*/m', '', $ddev);
    $write('.ddev/config.yaml', $ddev);
    $done[] = "DDEV: project {$slug}, https://{$slug}.ddev.site";
}

// CI: test the versions in composer.lock (the skeleton tests the framework's main branch instead),
// and let Dependabot update the PHP dependencies too.
if (($ci = $read('.github/workflows/ci.yml')) !== null) {
    $write('.github/workflows/ci.yml', (string) preg_replace(
        '/ *# No lock file in the skeleton\..*\n.*\n( *)- name: Install PHP dependencies \(framework main\)\n *run: \|\n *composer config prefer-stable false\n *composer update (.*)\n/',
        "$1- name: Install PHP dependencies\n$1  run: composer install $2\n",
        $ci,
    ));
}
if (($dependabot = $read('.github/dependabot.yml')) !== null) {
    $dependabot = (string) preg_replace('/^# Weekly.*\n(?:# .*\n)*/m', "# Weekly pull requests for dependency updates and security advisories.\n", $dependabot, 1);
    $write('.github/dependabot.yml', str_replace("updates:\n", "updates:\n  - package-ecosystem: composer\n    directory: /\n    schedule:\n      interval: weekly\n", $dependabot));
}

// README: about your site, not about TinyTank.
$write('README.md', <<<MD
    # {$slug}

    Built with [TinyTank](https://tinytank.dev/) ([documentation](https://docs.tinytank.dev/)).

    ```sh
    ddev start
    ddev npm install && ddev npm run dev     # https://{$slug}.ddev.site
    ddev composer test
    ```

    Next: the [Starting a site](https://docs.tinytank.dev/1.x/getting-started/checklist) checklist.

    MD);

// TinyTank's examples: kept to learn from, or removed now (the same as `php bin/console site:clean`).
$options = array_slice($argv, 1);
$keep = match (true) {
    in_array('--remove-examples', $options, true) => false,
    in_array('--keep-examples', $options, true) => true,
    function_exists('stream_isatty') && stream_isatty(STDIN) => (static function (): bool {
        echo "\n  Keep TinyTank's examples (demos on the home page, sample posts, an Italian translation)?\n"
            . "  They show how things work; `php bin/console site:clean` removes them any time. [Y/n] ";
        $answer = strtolower(trim((string) fgets(STDIN)));

        return $answer === '' || $answer[0] !== 'n';
    })(),
    default => true,
};
if (!$keep) {
    if (!is_file("{$root}/vendor/autoload.php")) {
        fwrite(STDERR, "  Can't remove the examples before `composer install`: run `php bin/console site:clean` afterwards.\n");
    } else {
        require "{$root}/vendor/autoload.php";
        (new TinyTank\Console\Examples($root))->clean();
        $done[] = 'removed TinyTank\'s examples: the site starts clean';
    }
} elseif (is_file("{$root}/.examples")) {
    $done[] = 'kept TinyTank\'s examples: `php bin/console site:clean` removes them when you\'re done';
}

// Files about developing TinyTank itself, and this script with its test.
foreach (['ROADMAP.md', 'CONTRIBUTING.md', 'LICENSE', 'SECURITY.md', 'tests/CreateSiteTest.php', 'bin/create-site.php'] as $file) {
    if (is_file("{$root}/{$file}")) {
        unlink("{$root}/{$file}");
    }
}
$done[] = 'removed TinyTank\'s own ROADMAP, CONTRIBUTING, LICENSE and SECURITY files, and this script';

echo "\n  Your TinyTank site \"{$slug}\" is ready:\n" . implode('', array_map(static fn (string $line) => "  ✔ {$line}\n", $done))
    . "\n  Next: https://docs.tinytank.dev/1.x/getting-started/checklist\n\n";
