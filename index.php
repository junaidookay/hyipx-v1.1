<?php

// Serve static files directly when using PHP built-in server
if (php_sapi_name() === 'cli-server') {
    $path = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (is_file($path)) {
        return false;
    }
}

// Path to the installed file in the current directory
$installedFilePath = __DIR__ . '/installed';

// If not installed, redirect to install folder in current path
if (!file_exists($installedFilePath)) {

    // Get current directory URL path
    $currentPath = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');

    header("Location: {$currentPath}/install");
    exit;
}

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Check maintenance mode
if (file_exists($maintenance = __DIR__ . '/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register Composer autoloader
require __DIR__ . '/core/vendor/autoload.php';

// Bootstrap Laravel and handle request
(require_once __DIR__ . '/core/bootstrap/app.php')
    ->handleRequest(Request::capture());
