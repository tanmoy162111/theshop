<?php

/**
 * Router for PHP's built-in server.
 *
 * This app uses the CodeCanyon shared-hosting layout: index.php sits at the
 * project root (not public/index.php), and assets live under /public/*.
 * Real files (assets, uploads) are served directly; everything else is
 * handed to the Laravel front controller at index.php.
 */

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$requested = __DIR__ . $uri;

if ($uri !== '/' && file_exists($requested) && !is_dir($requested)) {
    return false; // let the built-in server serve the static file as-is
}

require_once __DIR__ . '/index.php';
