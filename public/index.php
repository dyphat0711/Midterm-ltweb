<?php
/**
 * AetherMind Front Controller
 * Single entry point for all application requests
 */

// Show error messages for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

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
