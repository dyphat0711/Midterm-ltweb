<?php
/**
 * AetherMind Base Controller Class
 */

class Controller {
    /**
     * Render a view wrapped in the main HTML layout
     */
    public function view($view, $data = []) {
        // Path to view file
        $viewFile = APP_ROOT . '/views/' . $view . '.php';

        if (file_exists($viewFile)) {
            // Extract data into local variable scope
            extract($data);

            // Start output buffering to capture the view contents
            ob_start();
            require_once $viewFile;
            $content = ob_get_clean();

            // Render the global layout, which will include the captured $content
            require_once APP_ROOT . '/views/layouts/main.php';
        } else {
            die("View '{$view}' does not exist.");
        }
    }

    /**
     * Send a JSON response
     */
    public function json($data, $statusCode = 200) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }
}
