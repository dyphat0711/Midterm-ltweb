<?php
/**
 * AetherMind Configuration File
 */

// Define directory constants relative to this file
define('APP_ROOT', dirname(__DIR__));
define('URL_ROOT', 'http://localhost:8000');
define('SITE_NAME', 'AetherMind');

// SQLite Database Path
define('DB_PATH', APP_ROOT . '/database/database.sqlite');

// Default API Config (Gemini API can also be provided by the user in-browser)
define('GEMINI_API_VERSION', 'v1beta');
define('GEMINI_MODEL', 'gemini-1.5-flash');
