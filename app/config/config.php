<?php
/**
 * AetherMind Configuration File
 */

// Define directory constants relative to this file
define('APP_ROOT', dirname(__DIR__));

function aethermind_url_root() {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['SERVER_PORT'] ?? '') === '443');
    $scheme = $https ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost:8000';
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
    $baseDir = str_replace('\\', '/', dirname($scriptName));

    if ($baseDir === '/' || $baseDir === '.') {
        $baseDir = '';
    }

    return $scheme . '://' . $host . $baseDir;
}

define('URL_ROOT', aethermind_url_root());
define('SITE_NAME', 'AetherMind');

// SQLite / JSON Database Path
define('DB_PATH', APP_ROOT . '/database/database.sqlite');

// Database Config (Supports 'json' or 'mysql' on XAMPP)
define('DB_TYPE', 'mysql'); // Change to 'mysql' to use MySQL on XAMPP
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'aethermind');
define('DB_PORT', '3306');

// Default API Config (Gemini API can also be provided by the user in-browser)
define('GEMINI_API_VERSION', 'v1beta');
define('GEMINI_MODEL', 'gemini-flash-latest');

