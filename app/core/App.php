<?php
/**
 * AetherMind Router Engine
 * Handles URL routing relative to the project entry point
 */

class App {
    protected $controller = 'HomeController';
    protected $method = 'index';
    protected $params = [];

    public function __construct() {
        $url = $this->parseUrl();

        // 1. Determine Controller Name
        $controllerName = 'HomeController';
        if (isset($url[0]) && $url[0] !== '') {
            $candidate = ucfirst($url[0]) . 'Controller';
            if (file_exists(APP_ROOT . '/controllers/' . $candidate . '.php')) {
                $controllerName = $candidate;
                array_shift($url);
            } else {
                // If controller doesn't exist, we fall back to a 404 or HomeController
                // For APIs, let's check ApiController
                $apiCandidate = 'ApiController';
                if ($url[0] === 'api' && isset($url[1])) {
                    $controllerName = 'ApiController';
                    array_shift($url); // remove 'api'
                }
            }
        }

        // Require the controller class
        require_once APP_ROOT . '/controllers/' . $controllerName . '.php';
        $this->controller = new $controllerName();

        // 2. Determine Method Name
        if (isset($url[0]) && $url[0] !== '') {
            if (method_exists($this->controller, $url[0])) {
                $this->method = $url[0];
                array_shift($url);
            }
        }

        // 3. Extract remaining parameters
        $this->params = $url ? array_values($url) : [];

        // 4. Call the controller action
        call_user_func_array([$this->controller, $this->method], $this->params);
    }

    /**
     * Parses the request URI and converts it into a relative array of segments
     */
    private function parseUrl() {
        // Retrieve Request URI
        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
        
        // Remove query strings if present (e.g. /api/search?q=test -> /api/search)
        $cleanUri = parse_url($requestUri, PHP_URL_PATH);

        // Detect project subdirectory/basepath to strip it
        // E.g., if script is running in /LTWeb-App/Midterm/public/index.php
        $scriptName = $_SERVER['SCRIPT_NAME']; // /LTWeb-App/Midterm/public/index.php
        $baseDir = dirname($scriptName);      // /LTWeb-App/Midterm/public

        // If index.php is at the server root, baseDir is '/' or '\', make it empty
        if ($baseDir === '/' || $baseDir === '\\') {
            $baseDir = '';
        }

        // Strip the base directory prefix from the clean URI
        if ($baseDir !== '' && strpos($cleanUri, $baseDir) === 0) {
            $cleanUri = substr($cleanUri, strlen($baseDir));
        }

        // Strip leading and trailing slashes, then split into segments
        $trimmedUri = trim($cleanUri, '/');
        
        return $trimmedUri !== '' ? explode('/', $trimmedUri) : [];
    }
}
