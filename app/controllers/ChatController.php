<?php
/**
 * AetherMind Chat Controller
 * Manages chat layouts, message feeds, and logs persistence
 */

class ChatController extends Controller {
    private $chatModel;

    public function __construct() {
        require_once APP_ROOT . '/models/ChatMessage.php';
        $this->chatModel = new ChatMessage();
    }

    /**
     * Render the Chat Workspace view
     */
    public function index() {
        $data = [
            'title' => 'Không gian AI'
        ];
        $this->view('chatbot/index', $data);
    }

    /**
     * API: Get conversation logs
     */
    public function history() {
        try {
            $history = $this->chatModel->getHistory();
            $this->json(['success' => true, 'history' => $history]);
        } catch (Exception $e) {
            $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * API: Clear logs
     */
    public function clear() {
        try {
            $this->chatModel->clearHistory();
            $this->json(['success' => true, 'message' => 'Đã xóa lịch sử thành công']);
        } catch (Exception $e) {
            $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
