<?php
// bootstrap.php

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/bootstrap_php-error.log.txt');

final class PathHelper {
    private static bool $inited = false;
    private static string $documentRoot;
    private static string $webBase;
    private static string $fsLoginRoot;
    private static string $urlLoginRoot;
    
    public static function init(string $webBase, string $loginDir = 'login'): void {
        self::$documentRoot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', "/\\");
        self::$webBase = rtrim($webBase, '/');
        $fsBase = self::$documentRoot . (self::$webBase !== '' ? self::$webBase : '');
        self::$fsLoginRoot = rtrim(self::normalizeFs($fsBase . '/' . $loginDir), "/\\");
        self::$urlLoginRoot = rtrim(self::$webBase . '/' . $loginDir, '/');
        self::$inited = true;
    }
    
    public static function url(string $pathFromLogin = ''): string {
        self::ensureInit();
        $pathFromLogin = ltrim($pathFromLogin, "/\\");
        $pathFromLogin = str_replace('\\', '/', $pathFromLogin);
        return self::$urlLoginRoot . ($pathFromLogin !== '' ? '/' . $pathFromLogin : '');
    }
    
    public static function fs(string $pathFromLogin = ''): string {
        self::ensureInit();
        $pathFromLogin = ltrim($pathFromLogin, "/\\");
        return self::normalizeFs(self::$fsLoginRoot . ($pathFromLogin !== '' ? '/' . $pathFromLogin : ''));
    }
    
    private static function ensureInit(): void {
        if (!self::$inited) {
            throw new RuntimeException("PathHelper not initialized. Call PathHelper::init(...) in bootstrap.");
        }
    }
    
    private static function normalizeFs(string $path): string {
        $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        $path = preg_replace('#' . preg_quote(DIRECTORY_SEPARATOR) . '{2,}#', DIRECTORY_SEPARATOR, $path);
        return $path;
    }
}

final class AppAutoloader {
    public static function register(): void {
        spl_autoload_register([self::class, 'autoload'], true, true);
    }
    
    public static function autoload(string $class): void {
        $class = ltrim($class, '\\');
        if ($class === '' || str_contains($class, '..')) return;
        
        $dirs = [
            PathHelper::fs('common'),
            PathHelper::fs('common/API'),
            PathHelper::fs('Mitglieder'),
            PathHelper::fs('Mitglieder/common'),
            PathHelper::fs('Mitglieder/common/API'),
        ];
        
        $candidates = [
            $class . '.php',
            $class . '_CLS.php',
            $class . '_API.php',
            $class . '_inc.php',
            $class . '_lib.php',
        ];
        
        if (str_contains($class, '\\')) {
            $short = substr($class, strrpos($class, '\\') + 1);
            array_unshift($candidates, $short . '.php', $short . '_CLS.php', $short . '_class.php');
        }
        
        foreach ($dirs as $dir) {
            foreach ($candidates as $file) {
                $full = rtrim($dir, "/\\") . DIRECTORY_SEPARATOR . $file;
                error_log("Autoloader prüft: $full");
                if (is_file($full)) {
                    error_log("Autoloader lädt: $full");
                    require_once $full;
                    return;
                }
            }
        }
    }
}
