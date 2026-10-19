<?php

declare(strict_types=1);

namespace App\Tests;

use PHPUnit\Framework\TestCase;
use TinyTank\Console\Examples;

/**
 * `php bin/console site:clean` leaves a working site: a copy of this site is cleaned, then its own
 * test suite runs on it (in a separate PHP process, with the copy's src/ and tests/). This test is
 * an example itself: cleaning removes it.
 */
final class CleanSiteTest extends TestCase
{
    private const ROOT = __DIR__ . '/..';
    /** Not copied: dependencies, builds, caches, and the maintainers' clones. */
    private const SKIPPED = ['vendor', 'node_modules', 'var', '.git', '.ddev', 'docs', 'packages', 'sites', 'test-results', 'public/build', 'public/media'];

    public function testTheCleanSitePassesItsOwnTests(): void
    {
        $copy = sys_get_temp_dir() . '/tinytank-clean-' . bin2hex(random_bytes(4));
        try {
            self::copy(realpath(self::ROOT) ?: self::ROOT, $copy);
            mkdir("{$copy}/var/cache", 0775, true);

            $plan = (new Examples($copy))->clean();
            self::assertContains('templates/_examples', $plan['delete']);
            self::assertFileDoesNotExist("{$copy}/.examples");
            self::assertFileDoesNotExist("{$copy}/tests/CleanSiteTest.php");
            self::assertFileDoesNotExist("{$copy}/translations/it.php");

            // Nothing left that points at what's gone.
            foreach (['ClockController', '_examples/', 'example' . ':start', "'it' => ['name'", 'Un micro framework', "'faq' => ["] as $leftover) {
                $found = self::grep($copy, $leftover);
                self::assertSame([], $found, "\"{$leftover}\" is still in: " . implode(', ', $found));
            }

            // The copy's PHPUnit suite, with the copy's own classes (Composer's map points at this site).
            $bootstrap = "{$copy}/var/clean-bootstrap.php";
            file_put_contents($bootstrap, '<?php $loader = require ' . var_export(realpath(self::ROOT . '/vendor/autoload.php'), true) . ";\n"
                . '$loader->setPsr4(\'App\\\\\', [' . var_export("{$copy}/src", true) . "]);\n"
                . '$loader->setPsr4(\'App\\\\Tests\\\\\', [' . var_export("{$copy}/tests", true) . "]);\n");
            $command = sprintf(
                '%s %s --configuration %s --bootstrap %s --no-progress --colors=never 2>&1',
                escapeshellarg(PHP_BINARY),
                escapeshellarg((string) realpath(self::ROOT . '/vendor/bin/phpunit')),
                escapeshellarg("{$copy}/phpunit.xml.dist"),
                escapeshellarg($bootstrap),
            );
            exec($command, $output, $code);
            self::assertSame(0, $code, "The clean site's tests failed:\n" . implode("\n", $output));
            // Its own suite (fewer tests than this one: the examples' tests went with them). Without a
            // build in the copy, the asset tests are skipped.
            self::assertMatchesRegularExpression('/\bOK\b/', implode("\n", $output));
            self::assertStringNotContainsString('CleanSiteTest', implode("\n", $output));
        } finally {
            self::remove($copy);
        }
    }

    private static function copy(string $from, string $to, string $relative = ''): void
    {
        mkdir($to, 0775, true);
        foreach (scandir($from) ?: [] as $name) {
            $path = ltrim("{$relative}/{$name}", '/');
            if ($name === '.' || $name === '..' || in_array($path, self::SKIPPED, true)) {
                continue;
            }
            if (is_dir("{$from}/{$name}") && !is_link("{$from}/{$name}")) {
                self::copy("{$from}/{$name}", "{$to}/{$name}", $path);
            } else {
                copy("{$from}/{$name}", "{$to}/{$name}");
            }
        }
    }

    /** @return list<string> files of the site's code that contain $text */
    private static function grep(string $root, string $text): array
    {
        $found = [];
        foreach (['config', 'src', 'templates', 'tests', 'resources'] as $dir) {
            foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator("{$root}/{$dir}", \FilesystemIterator::SKIP_DOTS)) as $file) {
                // CreateSiteTest checks the examples on purpose; create-site.php deletes it from every site.
                if ($file->isFile() && $file->getFilename() !== 'CreateSiteTest.php' && str_contains((string) file_get_contents($file->getPathname()), $text)) {
                    $found[] = substr($file->getPathname(), strlen($root) + 1);
                }
            }
        }

        return $found;
    }

    private static function remove(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST) as $file) {
            $file->isDir() && !$file->isLink() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
        rmdir($dir);
    }
}
