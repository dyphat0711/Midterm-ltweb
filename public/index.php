<?php
/**
 * AetherMind Front Controller
 * Single entry point for all application requests
 */

// Production Error Reporting: Hide errors from user, log them to server
error_reporting(E_ALL);
ini_set('display_errors', 0); // DO NOT output errors directly to the browser
ini_set('log_errors', 1);

// Configure Secure Session Parameters before starting session
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Lax');

// Set Security HTTP Headers
header("X-Frame-Options: SAMEORIGIN"); // Prevent clickjacking
header("X-XSS-Protection: 1; mode=block"); // Enable XSS filter
header("X-Content-Type-Options: nosniff"); // Prevent MIME-type sniffing
header("Referrer-Policy: strict-origin-when-cross-origin");

// Load Configurations
require_once __DIR__ . '/../app/config/config.php';

// Autoload MVC Core Classes
spl_autoload_register(function ($className) {
    // Check in core
    $corePath = APP_ROOT . '/core/' . $className . '.php';
    if (file_exists($corePath)) {
        require_once $corePath;
        return;
    }

    // Check in models
    $modelPath = APP_ROOT . '/models/' . $className . '.php';
    if (file_exists($modelPath)) {
        require_once $modelPath;
        return;
    }

    // Check in controllers
    $controllerPath = APP_ROOT . '/controllers/' . $className . '.php';
    if (file_exists($controllerPath)) {
        require_once $controllerPath;
        return;
    }
});

// Start Session
session_start();

// Initialize the Application Router
$app = new App();
