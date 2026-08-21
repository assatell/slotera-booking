<?php
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }
spl_autoload_register(static function(string $class): void {
    $prefix = 'Slotera\\';
    if (strpos($class, $prefix) !== 0) { return; }
    $relative = substr($class, strlen($prefix));
    $file = SLTR_PLUGIN_DIR . 'includes/' . str_replace('\\', DIRECTORY_SEPARATOR, $relative) . '.php';
    if (file_exists($file)) { require_once $file; }
});
