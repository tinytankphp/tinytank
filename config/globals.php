<?php

declare(strict_types=1);

// Values every template can read as `globals`, nested as deep as you like:
//
//   'socials' => ['github' => 'https://github.com/…'],   →   {{ globals.socials.github }}
//
// Everything here can end up in a page: never put secrets in it (they belong in .env).
// https://docs.tinytank.dev/1.x/basics/templates#global-values

return [
];
