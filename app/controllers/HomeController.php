<?php
/**
 * AetherMind Home Controller
 * Manages dashboard statistical landing pages
 */

class HomeController extends Controller {
    private $chatModel;
    private $documentModel;

    public function __construct() {
        // Load models
        require_once APP_ROOT . '/models/ChatMessage.php';
        require_once APP_ROOT . '/models/Document.php';
        $this->chatModel = new ChatMessage();
        $this->documentModel = new Document();
    }

    /**
     * Render the core Dashboard landing view
     */
    public function index() {
        // Get counts and analytics summaries
        $analytics = $this->chatModel->getAnalyticsSummary();
        $docs = $this->documentModel->getAll();
        
        $totalDocs = count($docs);
        $totalWords = 0;
        foreach ($docs as $doc) {
            $totalWords += $doc->word_count;
        }

        $data = [
            'title' => 'Bảng điều khiển',
            'total_chats' => $analytics['total_chats'],
            'avg_sentiment' => $analytics['avg_sentiment'],
            'sentiment_breakdown' => $analytics['sentiment_breakdown'],
            'model_usage' => $analytics['model_usage'],
            'total_documents' => $totalDocs,
            'total_words' => $totalWords,
            'documents' => $docs
        ];

        $this->view('home/index', $data);
    }

    /**
     * API Endpoint: Return analytics payload as JSON
     */
    public function analytics() {
        $analytics = $this->chatModel->getAnalyticsSummary();
        
        // Fetch monthly chat logs or simple counts by day to draw a chart
        $db = Database::getInstance();
        $dailyChats = $db->query("
            SELECT date(created_at) as day, COUNT(*) as count 
            FROM chat_history 
            GROUP BY day 
            ORDER BY day ASC 
            LIMIT 15
        ")->fetchAll();

        $payload = [
            'summary' => $analytics,
            'daily_chats' => $dailyChats
        ];

        $this->json($payload);
    }
}
