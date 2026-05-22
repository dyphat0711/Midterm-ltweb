<?php
/**
 * AetherMind Visualizer Controller
 * Serves the top-level Vector Space route used by the sidebar navigation.
 */

class VisualizerController extends Controller {
    public function index() {
        $data = [
            'title' => 'Không gian vector'
        ];

        $this->view('visualizer/index', $data);
    }
}
