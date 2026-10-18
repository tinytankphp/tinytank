<?php

declare(strict_types=1);

// PHP's built-in server (`php -S localhost:8000 -t public public/index.php`): files that exist in
// public/ (built CSS and JS, images) are served as they are, like nginx's try_files.
if (PHP_SAPI === 'cli-server') {
    $file = realpath(__DIR__ . parse_url((string) $_SERVER['REQUEST_URI'], PHP_URL_PATH));
    if ($file !== false && is_file($file) && str_starts_with($file, __DIR__ . DIRECTORY_SEPARATOR)) {
        return false;
    }
}

require dirname(__DIR__) . '/vendor/autoload.php';

// Routes live in config/routes.php so bin/console can compile them too.
TinyTank\Kernel::boot(dirname(__DIR__))->run();
