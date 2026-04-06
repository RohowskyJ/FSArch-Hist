<?php

declare(strict_types=1);

if (defined('FSARCH_BOOTSTRAP')) {
    return;
}
define('FSARCH_BOOTSTRAP', true);

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/bootstrap_php-error.log.txt');

$rootAutoload = __DIR__ . '/../vendor/autoload.php';
if (is_file($rootAutoload)) {
    require_once $rootAutoload;
}

$commonAutoload = __DIR__ . '/vendor/autoload.php';
if (is_file($commonAutoload)) {
    require_once $commonAutoload;
}

/**
 * Initialize the legacy path helper and Composer autoload layers.
 *
 * @param string|null $webBase  Optional web base path, e.g. '/FSArch_Hist'
 * @param string $loginDir      Login directory relative to web root
 *
 * @return void
 */
function fsarch_bootstrap_path_init(?string $webBase = null, string $loginDir = 'login'): void
{
    if (!class_exists('PathHelper')) {
        $helperFile = __DIR__ . '/BS_BootPfadL_CLS.php';
        if (!is_file($helperFile)) {
            throw new RuntimeException("PathHelper helper not found in $helperFile");
        }
        require_once $helperFile;
    }

    if ($webBase === null) {
        $webBase = fsarch_detect_web_base($loginDir);
    }

    PathHelper::init($webBase, $loginDir);
//    AppAutoloader::register();
}

/**
 * Try to detect the web base path from the current request URI.
 *
 * @param string $loginDir
 * @return string
 */
function fsarch_detect_web_base(string $loginDir = 'login'): string
{
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    $path = parse_url($uri, PHP_URL_PATH) ?: '';
    $segments = array_values(array_filter(explode('/', $path), static fn($segment) => $segment !== ''));

    if (count($segments) === 0) {
        return '';
    }

    if (strtolower($segments[0]) === strtolower($loginDir)) {
        return '';
    }

    return '/' . $segments[0];
}
