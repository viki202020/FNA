<?php

/**
 * Local testing helper for PHP's built-in web server:
 *   php -S localhost:8000 dev-router.php
 *
 * The views link to /vendor/... and /assets/... (and /public/...), but the
 * real files live in the public/ folder. This serves them from there and
 * sends everything else to Laravel as usual.
 */

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$uri = preg_replace('#^/public(?=/)#', '', $uri);
$uri = preg_replace('#^(/\.\./)+#', '/', $uri);

$file = realpath(__DIR__.'/public'.$uri);

if ($uri !== '/' && $file !== false && is_file($file)
    && strpos($file, realpath(__DIR__.'/public')) === 0
    && pathinfo($file, PATHINFO_EXTENSION) !== 'php') {
    $types = [
        'js' => 'application/javascript', 'css' => 'text/css',
        'svg' => 'image/svg+xml', 'png' => 'image/png', 'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg', 'gif' => 'image/gif', 'ico' => 'image/x-icon',
        'json' => 'application/json', 'woff' => 'font/woff', 'woff2' => 'font/woff2',
        'ttf' => 'font/ttf', 'map' => 'application/json',
    ];
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    header('Content-Type: '.($types[$ext] ?? 'application/octet-stream'));
    readfile($file);
    return true;
}

require_once __DIR__.'/public/index.php';
