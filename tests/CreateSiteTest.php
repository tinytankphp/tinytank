<?php

declare(strict_types=1);

namespace App\Tests;

use TinyTank\Testing\KernelTestCase;

/**
 * bin/create-site.php, which Composer runs after `composer create-project tinytank/tinytank`: run on
 * a copy of the skeleton's tracked files. (The script removes this test from the sites it creates.)
 */
final class CreateSiteTest extends KernelTestCase
{
    public function testAFreshCopyBecomesTheSite(): void
    {
        $site = $this->skeletonCopy();
        exec('php ' . escapeshellarg("{$site}/bin/create-site.php") . ' 2>&1', $output, $code);

        self::assertSame(0, $code, implode("\n", $output));
        self::assertStringContainsString('Your TinyTank site "my-shop" is ready', implode("\n", $output));

        $composer = json_decode((string) file_get_contents("{$site}/composer.json"), true);
        self::assertSame(['app/my-shop', 'proprietary'], [$composer['name'], $composer['license']]);
        self::assertArrayNotHasKey('post-create-project-cmd', $composer['scripts']);
        self::assertArrayHasKey('tinytank/framework', $composer['require']);
        self::assertSame('my-shop', json_decode((string) file_get_contents("{$site}/package.json"), true)['name']);
        self::assertSame('my-shop', json_decode((string) file_get_contents("{$site}/package-lock.json"), true)['packages']['']['name']);

        $env = (string) file_get_contents("{$site}/.env");
        self::assertMatchesRegularExpression('/^APP_SECRET=[0-9a-f]{64}$/m', $env);
        self::assertStringContainsString("APP_URL=https://my-shop.ddev.site\n", $env);
        self::assertStringContainsString("APP_DEBUG=1\n", $env);

        self::assertStringNotContainsString('composer.lock', (string) file_get_contents("{$site}/.gitignore"));

        $ddev = (string) file_get_contents("{$site}/.ddev/config.yaml");
        self::assertStringStartsWith("name: my-shop\n", $ddev);
        self::assertStringNotContainsString('tinytank', $ddev);
        self::assertStringNotContainsString('5174', $ddev, 'the docs port is for TinyTank maintainers');
        self::assertStringContainsString('container_port: 5173', $ddev, 'Vite stays');
        self::assertStringContainsString('container_port: 8080', $ddev, 'the static preview stays');

        $ci = (string) file_get_contents("{$site}/.github/workflows/ci.yml");
        self::assertStringContainsString('run: composer install --no-interaction', $ci);
        self::assertStringNotContainsString('prefer-stable', $ci);
        self::assertStringContainsString('package-ecosystem: composer', (string) file_get_contents("{$site}/.github/dependabot.yml"));

        self::assertStringStartsWith("# my-shop\n", (string) file_get_contents("{$site}/README.md"));
        foreach (['ROADMAP.md', 'CONTRIBUTING.md', 'LICENSE', 'SECURITY.md', 'bin/create-site.php', 'tests/CreateSiteTest.php'] as $removed) {
            self::assertFileDoesNotExist("{$site}/{$removed}");
        }
        self::assertFileExists("{$site}/content/pages/style-guide/index.md", 'starter content stays: the checklist decides');
        self::assertFileExists("{$site}/AGENTS.md", 'instructions for coding agents stay with the site');

        // Without a terminal (as here, or with --no-interaction), the examples stay, and it says how to remove them.
        self::assertFileExists("{$site}/.examples");
        self::assertFileExists("{$site}/templates/_examples/home.twig");
        self::assertStringContainsString('kept TinyTank\'s examples: `php bin/console site:clean` removes them', implode("\n", $output));
    }

    public function testTheExamplesCanGoRightAway(): void
    {
        $site = $this->skeletonCopy();
        symlink(dirname(__DIR__) . '/vendor', "{$site}/vendor"); // as after `composer create-project`
        exec('php ' . escapeshellarg("{$site}/bin/create-site.php") . ' --remove-examples 2>&1', $output, $code);

        self::assertSame(0, $code, implode("\n", $output));
        self::assertStringContainsString('removed TinyTank\'s examples', implode("\n", $output));
        self::assertFileDoesNotExist("{$site}/.examples");
        self::assertFileDoesNotExist("{$site}/templates/_examples/home.twig");
        self::assertFileDoesNotExist("{$site}/translations/it.php");
        self::assertStringNotContainsString('example' . ':start', (string) file_get_contents("{$site}/config/routes.php")); // split: not a marker itself
        self::assertFileExists("{$site}/content/pages/style-guide/index.md", 'the style guide is no example');
    }

    public function testAnExistingEnvIsKept(): void
    {
        $site = $this->skeletonCopy();
        file_put_contents("{$site}/.env", "APP_SECRET=mine\n");
        exec('php ' . escapeshellarg("{$site}/bin/create-site.php"), $output, $code);

        self::assertSame("APP_SECRET=mine\n", file_get_contents("{$site}/.env"));
    }

    /** The skeleton's tracked files, in a folder named "My Shop" (as `composer create-project` gets them). */
    private function skeletonCopy(): string
    {
        $root = dirname(__DIR__);
        // Tracked files plus new ones not ignored yet (what the next commit would contain).
        exec('git -C ' . escapeshellarg($root) . ' ls-files -z --cached --others --exclude-standard 2>/dev/null', $lines, $code);
        if ($code !== 0 || $lines === []) {
            self::markTestSkipped('Needs the skeleton as a git checkout.');
        }
        $site = $this->tempDir('site') . '/My Shop';
        foreach (array_filter(explode("\0", implode("\n", $lines))) as $file) {
            if (!is_file("{$root}/{$file}")) {
                continue;
            }
            @mkdir(dirname("{$site}/{$file}"), 0777, true);
            copy("{$root}/{$file}", "{$site}/{$file}");
        }

        return $site;
    }
}
