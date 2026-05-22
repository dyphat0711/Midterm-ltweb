<?php
/**
 * AetherMind API Controller
 * Provides clean REST endpoints and secure cURL proxies
 */

class ApiController extends Controller {
    private $chatModel;
    private $documentModel;

    public function __construct() {
        require_once APP_ROOT . '/models/ChatMessage.php';
        require_once APP_ROOT . '/models/Document.php';
        $this->chatModel = new ChatMessage();
        $this->documentModel = new Document();
    }

    /**
     * POST /api/chat
     * Unified Chat Endpoint - Supports Single Agent and Collaborative Multi-Agent Modes
     */
    public function chat() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'error' => 'Phương thức không được hỗ trợ'], 405);
        }

        // Parse JSON inputs
        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true);

        if (!$data || empty($data['message'])) {
            $this->json(['success' => false, 'error' => 'Thiếu nội dung tin nhắn'], 400);
        }

        $userMessage = trim($data['message']);
        $sentiment = $data['sentiment'] ?? 'Trung tính';
        $sentimentScore = floatval($data['sentiment_score'] ?? 0.5);
        $modelMode = $data['model'] ?? 'Offline Simulation'; // 'Gemini' or 'Offline Simulation'
        $clientApiKey = $data['apiKey'] ?? ''; // Optional client key override
        $ragEnabled = !empty($data['ragEnabled']);
        $agent = $data['agent'] ?? 'general'; // 'general', 'auditor', 'analyst', 'collaboration'

        try {
            // 1. Save user's message to database
            $this->chatModel->save('user', $userMessage, $sentiment, $sentimentScore, $modelMode);

            // 2. Process Semantic Context passed from the client-side Universal Sentence Encoder
            $ragPayload = ['text' => '', 'matches' => [], 'citations' => ''];
            if ($ragEnabled && isset($data['ragMatches']) && is_array($data['ragMatches'])) {
                $ragPayload['matches'] = $data['ragMatches'];
                
                // Construct textual context from semantic chunks
                $contextTexts = [];
                $docCitations = [];
                foreach ($data['ragMatches'] as $match) {
                    $contextTexts[] = "Tài liệu: " . $match['document_title'] . "\nNội dung: " . $match['content'];
                    $docCitations[] = $match['document_title'];
                }
                
                if (!empty($contextTexts)) {
                    $ragPayload['text'] = implode("\n\n---\n\n", $contextTexts);
                    $ragPayload['citations'] = implode(', ', array_unique($docCitations));
                }
            }
            $ragContext = $ragPayload['text'];
            $ragCitations = $ragPayload['citations'];

            // Fetch Gemini API Key if online mode
            $apiKey = '';
            if ($modelMode === 'Gemini') {
                $apiKey = !empty($clientApiKey) ? $clientApiKey : (defined('GEMINI_API_KEY') ? GEMINI_API_KEY : '');
                if (empty($apiKey)) {
                    $modelMode = 'Offline Simulation (Fallback)';
                }
            }

            if ($agent === 'collaboration') {
                // MULTI-AGENT COLLABORATION PIPELINE
                
                // Agent 1: Sentiment Auditor
                $auditorResponse = '';
                if ($modelMode === 'Gemini' && !empty($apiKey)) {
                    $prompt = "Hãy phân tích chỉ số cảm xúc khi người dùng nhập (cảm xúc: {$sentiment}, điểm: {$sentimentScore}) và đưa ra nhận xét ngắn, hỗ trợ, liên quan đến câu hỏi: \"{$userMessage}\". Giữ dưới 80 từ.";
                    $auditorSys = "Bạn là tác tử Kiểm toán cảm xúc trong AetherMind. Hãy chuyên nghiệp, đồng cảm, ngắn gọn và nói trực tiếp với người dùng.";
                    $auditorResponse = $this->queryGemini($prompt, $apiKey, '', $auditorSys);
                } else {
                    $auditorResponse = $this->getMockAuditorResponse($userMessage, $sentiment, $sentimentScore);
                }
                $this->chatModel->save('assistant', $auditorResponse, 'Trung tính', 0.5, 'Kiểm toán cảm xúc');

                // Agent 2: Doc-Analyst
                $analystResponse = '';
                if ($modelMode === 'Gemini' && !empty($apiKey)) {
                    $prompt = "Phân tích dữ kiện dựa chặt chẽ trên các đoạn ngữ cảnh tài liệu cho truy vấn: \"{$userMessage}\". Các đoạn:\n{$ragContext}";
                    $analystSys = "Bạn là tác tử Doc-Analyst trong AetherMind. Chỉ phân tích dựa trên ngữ cảnh tài liệu đã truy xuất và nêu tiêu đề tài liệu nguồn. Nếu không có ngữ cảnh, hãy nói lịch sự rằng chưa tìm thấy tài liệu phù hợp. Giữ dưới 100 từ.";
                    $analystResponse = $this->queryGemini($prompt, $apiKey, $ragContext, $analystSys);
                } else {
                    $analystResponse = $this->getMockAnalystResponse($userMessage, $ragContext);
                }
                $this->chatModel->save('assistant', $analystResponse, 'Trung tính', 0.5, 'Doc-Analyst');

                // Agent 3: AetherMind (Core Generalist)
                $generalResponse = '';
                if ($modelMode === 'Gemini' && !empty($apiKey)) {
                    $prompt = "Truy vấn người dùng: \"{$userMessage}\"\n\nPhân tích cảm xúc:\n{$auditorResponse}\n\nPhân tích tài liệu:\n{$analystResponse}\n\nHãy tổng hợp các thông tin này để tạo câu trả lời cuối cùng rõ ràng, có cấu trúc và giải quyết truy vấn.";
                    $generalSys = "Bạn là AetherMind, tác tử tổng hợp chính. Hãy dùng phân tích cảm xúc và dữ kiện tài liệu để viết câu trả lời cuối cùng, mạch lạc và ngắn gọn.";
                    $generalResponse = $this->queryGemini($prompt, $apiKey, $ragContext, $generalSys);
                } else {
                    $generalResponse = $this->getMockResponse($userMessage, $ragContext);
                }
                $this->chatModel->save('assistant', $generalResponse, 'Trung tính', 0.5, 'AetherMind');

                $this->json([
                    'success' => true,
                    'mode' => 'collaboration',
                    'responses' => [
                        'auditor' => $auditorResponse,
                        'analyst' => $analystResponse,
                        'generalist' => $generalResponse
                    ],
                    'model' => $modelMode,
                    'rag_context_used' => !empty($ragContext),
                    'rag_matches' => $ragPayload['matches']
                ]);

            } else {
                // SINGLE AGENT MODE
                $assistantResponse = '';
                $savedModelName = 'Mô phỏng offline';

                if ($agent === 'auditor') {
                    $savedModelName = 'Kiểm toán cảm xúc';
                    if ($modelMode === 'Gemini' && !empty($apiKey)) {
                        $prompt = "Hãy phân tích chỉ số cảm xúc khi người dùng nhập (cảm xúc: {$sentiment}, điểm: {$sentimentScore}) và đưa ra nhận xét ngắn liên quan đến truy vấn: \"{$userMessage}\". Giữ dưới 80 từ.";
                        $sys = "Bạn là tác tử Kiểm toán cảm xúc trong AetherMind. Hãy nói trực tiếp với người dùng.";
                        $assistantResponse = $this->queryGemini($prompt, $apiKey, '', $sys);
                    } else {
                        $assistantResponse = $this->getMockAuditorResponse($userMessage, $sentiment, $sentimentScore);
                    }
                } elseif ($agent === 'analyst') {
                    $savedModelName = 'Doc-Analyst';
                    if ($modelMode === 'Gemini' && !empty($apiKey)) {
                        $prompt = "Phân tích dữ kiện của truy vấn: \"{$userMessage}\" dựa trên các đoạn ngữ cảnh sau:\n{$ragContext}";
                        $sys = "Bạn là tác tử Doc-Analyst trong AetherMind. Chỉ phân tích bằng ngữ cảnh tài liệu và nêu tiêu đề nguồn. Nếu ngữ cảnh rỗng, hãy nói rõ. Giữ dưới 100 từ.";
                        $assistantResponse = $this->queryGemini($prompt, $apiKey, $ragContext, $sys);
                    } else {
                        $assistantResponse = $this->getMockAnalystResponse($userMessage, $ragContext);
                    }
                } else {
                    // Default core agent: AetherMind
                    $savedModelName = ($modelMode === 'Gemini') ? 'AetherMind (Gemini)' : 'AetherMind (cục bộ)';
                    if ($modelMode === 'Gemini' && !empty($apiKey)) {
                        $assistantResponse = $this->queryGemini($userMessage, $apiKey, $ragContext);
                    } else {
                        if ($modelMode === 'Offline Simulation (Fallback)') {
                            $assistantResponse = "**Thiếu Gemini API Key.** Tôi đang trả lời bằng **chế độ mô phỏng offline**.\n\nAPI key được proxy qua backend PHP cục bộ. Hãy mở **Cài đặt API** ở thanh bên trái để thêm Gemini API Key.\n\n*Phản hồi:* " . $this->getMockResponse($userMessage, $ragContext);
                        } else {
                            $assistantResponse = $this->getMockResponse($userMessage, $ragContext);
                        }
                    }
                }

                // Save assistant response to DB
                $this->chatModel->save('assistant', $assistantResponse, 'Trung tính', 0.5, $savedModelName);

                $this->json([
                    'success' => true,
                    'mode' => 'single',
                    'agent' => $agent,
                    'response' => $assistantResponse,
                    'model' => $savedModelName,
                    'rag_context_used' => !empty($ragContext),
                    'rag_matches' => $ragPayload['matches']
                ]);
            }

        } catch (Exception $e) {
            $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * GET /api/history
     */
    public function history() {
        try {
            $history = $this->chatModel->getHistory(100);
            $this->json(['success' => true, 'history' => $history]);
        } catch (Exception $e) {
            $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/clear
     */
    public function clear() {
        try {
            $this->chatModel->clearHistory();
            $this->json(['success' => true, 'message' => 'Đã xóa lịch sử.']);
        } catch (Exception $e) {
            $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * GET /api/documents
     */
    public function documents() {
        try {
            $docs = $this->documentModel->getAll();
            $this->json(['success' => true, 'documents' => $docs]);
        } catch (Exception $e) {
            $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * GET /api/chunks
     */
    public function chunks() {
        try {
            $chunks = $this->documentModel->getAllChunks();
            $this->json(['success' => true, 'chunks' => $chunks]);
        } catch (Exception $e) {
            $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * GET /api/analytics
     */
    public function analytics() {
        try {
            $summary = $this->chatModel->getAnalyticsSummary();
            $db = Database::getInstance();
            $dailyChats = $db->query("
                SELECT date(created_at) as day, COUNT(*) as count
                FROM chat_history
                GROUP BY day
                ORDER BY day ASC
                LIMIT 15
            ")->fetchAll();

            $this->json([
                'success' => true,
                'summary' => $summary,
                'daily_chats' => $dailyChats
            ]);
        } catch (Exception $e) {
            $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/resetDemo
     */
    public function resetDemo() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'error' => 'Method not allowed'], 405);
        }

        try {
            $db = Database::getInstance();
            $db->resetDemoData();
            if (session_status() === PHP_SESSION_ACTIVE) {
                unset($_SESSION['rag_cache']);
            }
            $this->json(['success' => true, 'message' => 'Demo database reset successfully']);
        } catch (Exception $e) {
            $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/upload
     * Integrated parsing support for TXT, MD, CSV, DOCX (Word), article URLs, and manual copypastes
     */
    public function upload() {
        try {
            $title = '';
            $content = '';

            if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
                $fileName = $_FILES['file']['name'];
                $title = pathinfo($fileName, PATHINFO_FILENAME);
                $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                $tmpPath = $_FILES['file']['tmp_name'];
                $allowed = ['txt', 'md', 'csv', 'json', 'docx'];

                if (!in_array($ext, $allowed)) {
                    $this->json(['success' => false, 'error' => 'Unsupported file type. Use TXT, MD, CSV, JSON, DOCX, or parse PDF in the browser.'], 400);
                }

                if (($_FILES['file']['size'] ?? 0) > 8 * 1024 * 1024) {
                    $this->json(['success' => false, 'error' => 'File is too large. Maximum demo upload size is 8 MB.'], 400);
                }

                if ($ext === 'docx') {
                    $content = $this->parseDocx($tmpPath);
                } elseif ($ext === 'csv') {
                    $content = $this->parseCsv($tmpPath);
                } else {
                    // txt, md, or generic text
                    $content = file_get_contents($tmpPath);
                }
            } elseif (isset($_POST['url']) && trim($_POST['url']) !== '') {
                $article = $this->fetchArticleFromUrl(trim($_POST['url']));
                $title = $article['title'];
                $content = $article['content'];
            } elseif (isset($_POST['content']) && trim($_POST['content']) !== '') {
                    $title = isset($_POST['title']) && trim($_POST['title']) !== '' ? trim($_POST['title']) : 'Đoạn dán nhanh - ' . date('Y-m-d H:i');
                $content = $_POST['content'];
            } else {
                // Try JSON parsing
                $rawInput = file_get_contents('php://input');
                $data = json_decode($rawInput, true);
                if ($data && !empty($data['url'])) {
                    $article = $this->fetchArticleFromUrl(trim($data['url']));
                    $title = $article['title'];
                    $content = $article['content'];
                } elseif ($data && !empty($data['content'])) {
                    $title = $data['title'] ?? 'Tải lên qua API - ' . date('Y-m-d H:i');
                    $content = $data['content'];
                } else {
                    $this->json(['success' => false, 'error' => 'Chưa có nội dung được cung cấp'], 400);
                }
            }

            $content = trim($content);
            if ($content === '') {
                $this->json(['success' => false, 'error' => 'Content cannot be empty.'], 400);
            }

            $wordCount = count(preg_split('/\s+/', $content));
            if ($wordCount < 5) {
                $this->json(['success' => false, 'error' => 'Content is too short. Provide at least 5 words for useful chunking.'], 400);
            }

            $docId = $this->documentModel->create($title, $content);
            $this->json(['success' => true, 'document_id' => $docId, 'title' => $title]);
        } catch (InvalidArgumentException $e) {
            $this->json(['success' => false, 'error' => $e->getMessage()], 400);
        } catch (Exception $e) {
            $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/delete
     */
    public function delete($id = null) {
        $docId = $id;
        if ($docId === null) {
            $rawInput = file_get_contents('php://input');
            $data = json_decode($rawInput, true);
            $docId = $data['id'] ?? ($_POST['id'] ?? null);
        }

        if ($docId === null) {
            $this->json(['success' => false, 'error' => 'Thiếu ID tài liệu'], 400);
        }

        try {
            $success = $this->documentModel->delete($docId);
            $this->json(['success' => $success, 'message' => 'Đã xóa tài liệu']);
        } catch (Exception $e) {
            $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Query Google Gemini API using native PHP cURL
     */
    private function queryGemini($prompt, $apiKey, $ragContext = '', $customSystemInstruction = '') {
        $url = "https://generativelanguage.googleapis.com/" . GEMINI_API_VERSION . "/models/" . GEMINI_MODEL . ":generateContent?key=" . $apiKey;

        // Retrieve last 8 messages from chat log for conversation context
        $recentChats = $this->chatModel->getHistory(10);
        $contents = [];

        // Format history
        foreach ($recentChats as $chat) {
            if ($chat->model_used === 'System Seed') continue;
            $role = ($chat->sender === 'user') ? 'user' : 'model';
            $contents[] = [
                'role' => $role,
                'parts' => [['text' => $chat->message]]
            ];
        }

        // Setup system instructions
        $systemInstruction = !empty($customSystemInstruction) ? $customSystemInstruction : "Bạn là AetherMind, trợ lý AI dùng TensorFlow.js trên trình duyệt và backend PHP MVC.";
        if (!empty($ragContext) && empty($customSystemInstruction)) {
            $systemInstruction .= "\n\nBạn được cung cấp các đoạn tài liệu tham chiếu phù hợp từ workspace của người dùng. Hãy ưu tiên dùng ngữ cảnh này để trả lời chính xác:\n" . $ragContext;
        }

        // Add user prompt
        $contents[] = [
            'role' => 'user',
            'parts' => [['text' => $prompt]]
        ];

        // Body payload
        $payload = [
            'contents' => $contents,
            'systemInstruction' => [
                'parts' => [['text' => $systemInstruction]]
            ],
            'generationConfig' => [
                'temperature' => 0.7,
                'maxOutputTokens' => 800
            ]
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($httpCode !== 200) {
            $errData = json_decode($response, true);
            $errMsg = $errData['error']['message'] ?? 'Lỗi kết nối API';
            throw new Exception("Lỗi Gemini API (HTTP {$httpCode}): {$errMsg}");
        }

        $resData = json_decode($response, true);
        $replyText = $resData['candidates'][0]['content']['parts'][0]['text'] ?? '';

        if (empty($replyText)) {
            throw new Exception("Gemini API trả về phản hồi rỗng.");
        }

        return $replyText;
    }

    /**
     * Compute a local RAG context by doing a keyword-based similarity match against document chunks
     */
    private function getRagContext($query, $topK = 5) {
        $cacheKey = md5(mb_strtolower(trim($query), 'UTF-8') . '|' . $topK);
        if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['rag_cache'][$cacheKey])) {
            return $_SESSION['rag_cache'][$cacheKey];
        }

        $tokens = $this->tokenizeForRag($query);
        if (empty($tokens)) {
            return ['text' => '', 'matches' => [], 'citations' => ''];
        }

        $chunks = $this->documentModel->getAllChunks();
        $scored = [];

        foreach ($chunks as $chunk) {
            $content = mb_strtolower($chunk->content, 'UTF-8');
            $hits = 0;
            foreach ($tokens as $token) {
                $hits += substr_count($content, $token);
            }

            if ($hits <= 0) {
                continue;
            }

            $score = min(1, $hits / max(1, count($tokens) * 3));
            $scored[] = [
                'chunk_id' => (int)$chunk->id,
                'document_id' => (int)$chunk->document_id,
                'document_title' => $chunk->document_title ?? $chunk->title ?? 'Unknown',
                'chunk_index' => (int)$chunk->chunk_index,
                'score' => round($score, 4),
                'content' => $chunk->content
            ];
        }

        usort($scored, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        $matches = array_slice($scored, 0, $topK);
        $contextParts = [];
        $citationParts = [];

        foreach ($matches as $match) {
            $citation = "[Tài liệu: {$match['document_title']}, Đoạn: " . ($match['chunk_index'] + 1) . ", Điểm: {$match['score']}]";
            $citationParts[] = $citation;
            $contextParts[] = $citation . "\n" . $match['content'];
        }

        $payload = [
            'text' => implode("\n\n---\n\n", $contextParts),
            'matches' => $matches,
            'citations' => implode("\n", $citationParts)
        ];

        if (session_status() === PHP_SESSION_ACTIVE) {
            if (!isset($_SESSION['rag_cache']) || !is_array($_SESSION['rag_cache'])) {
                $_SESSION['rag_cache'] = [];
            }
            $_SESSION['rag_cache'][$cacheKey] = $payload;
        }

        return $payload;
    }

    private function tokenizeForRag($text) {
        $parts = preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($text, 'UTF-8'));
        $stopWords = ['the', 'and', 'for', 'that', 'this', 'with', 'from', 'của', 'và', 'là', 'có', 'cho', 'một', 'các', 'những', 'trong', 'thì', 'mà', 'đã', 'đang', 'sẽ', 'bị', 'được', 'như', 'nhưng', 'cũng', 'để'];
        return array_values(array_unique(array_filter($parts, function($token) use ($stopWords) {
            return mb_strlen($token, 'UTF-8') > 1 && !in_array($token, $stopWords);
        })));
    }

    /**
     * Return simulated intelligent response when offline
     */
    private function getMockResponse($query, $ragContext = '') {
        $q = mb_strtolower($query, 'UTF-8');
        $contextNote = !empty($ragContext) ? "\n\n*Ngữ cảnh RAG truy xuất từ dữ liệu cục bộ:*\n" . substr($ragContext, 0, 300) . "..." : "";

        if (strpos($q, 'hello') !== false || strpos($q, 'hi') !== false) {
            return "Xin chào! Tôi là **AetherMind**, trợ lý AI lai của bạn. Hiện tôi đang chạy ở **chế độ mô phỏng offline**. Bạn có thể đặt câu hỏi hoặc tải tài liệu lên để xem Vector Visualizer phản hồi theo thời gian thực. {$contextNote}";
        }
        
        if (strpos($q, 'sentiment') !== false) {
            return "Tôi phân tích cảm xúc theo thời gian thực ngay trong trình duyệt bằng **TensorFlow.js**. Vì việc xử lý diễn ra trên thiết bị, dữ liệu nhập của bạn không cần gửi ra ngoài. Hãy thử nhập một câu rất tích cực hoặc tiêu cực để xem thanh đo thay đổi. {$contextNote}";
        }

        if (strpos($q, 'rag') !== false || strpos($q, 'visualize') !== false || strpos($q, 'canvas') !== false) {
            return "**Vector Space Visualizer** dùng HTML5 Canvas để biểu diễn các đoạn tài liệu. Khi bạn tìm kiếm, node truy vấn sẽ kéo các đoạn phù hợp lại gần hơn bằng mô phỏng lực dựa trên độ tương đồng cosine. Hãy mở trang **Không gian vector** để xem trực tiếp. {$contextNote}";
        }

        if (!empty($ragContext)) {
            return "Dựa trên các tài liệu bạn đã tải lên, tôi tìm thấy nội dung sau:\n\n" . substr($ragContext, 0, 450) . "...\n\n*Phản hồi này được tạo cục bộ bằng cơ chế khớp chỉ mục mô phỏng.*";
        }

        return "Tôi đã nhận truy vấn: \"{$query}\".\n\nHiện tôi đang chạy ở **chế độ mô phỏng offline** vì chưa có Gemini API key. Để dùng khả năng suy luận cloud, hãy nhập Google Gemini API Key trong menu **Cài đặt API**. {$contextNote}";
    }

    /**
     * Mock Auditor Response
     */
    private function getMockAuditorResponse($userMessage, $sentiment, $sentimentScore) {
        return "**[Nhật ký kiểm toán cảm xúc]**\n" .
               "- Cảm xúc khi nhập: **{$sentiment}**\n" .
               "- Điểm cảm xúc: `{$sentimentScore}`\n\n" .
               "*Nhận xét:* Truy vấn \"*{$userMessage}*\" được nhập với sắc thái **{$sentiment}**. Tôi sẽ điều chỉnh cách phản hồi cho phù hợp với trạng thái này.";
    }

    /**
     * Mock Analyst Response
     */
    private function getMockAnalystResponse($userMessage, $ragContext) {
        if (empty($ragContext)) {
            return "**[Kết quả quét Doc-Analyst]**\n" .
                   "- Số đoạn phù hợp: `0`.\n\n" .
                   "*Trạng thái dữ kiện:* Tôi đã quét kho tri thức nhưng chưa tìm thấy đoạn văn bản phù hợp. Hãy tải lên `.txt`, `.pdf`, `.docx` hoặc `.csv` để mở rộng dữ liệu.";
        }
        
        return "**[Kết quả quét Doc-Analyst]**\n" .
               "- Tìm thấy các trích đoạn tài liệu phù hợp trong workspace:\n\n" .
               "{$ragContext}\n\n" .
               "*Trạng thái dữ kiện:* Các đoạn phù hợp đã được đưa vào không gian Vector Visualizer dưới dạng node đang hoạt động.";
    }

    /**
     * Fetch and extract readable text from a public HTML article URL.
     */
    private function fetchArticleFromUrl($url) {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException("URL khong hop le.");
        }

        $parts = parse_url($url);
        $scheme = strtolower($parts['scheme'] ?? '');
        $host = strtolower($parts['host'] ?? '');

        if (!in_array($scheme, ['http', 'https'])) {
            throw new InvalidArgumentException("Chi ho tro URL http/https.");
        }

        if ($this->isBlockedArticleHost($host)) {
            throw new InvalidArgumentException("Khong ho tro URL noi bo hoac dia chi khong an toan.");
        }

        $html = $this->downloadUrl($url);
        $title = $this->extractHtmlTitle($html);
        if ($title === '') {
            $title = 'Bai bao - ' . $host;
        }

        $content = $this->extractArticleText($html);
        $wordCount = ($content !== '') ? count(preg_split('/\s+/', trim($content))) : 0;
        if ($wordCount < 30) {
            throw new InvalidArgumentException("Khong trich xuat du noi dung tu URL. Trang co the chan crawler, can dang nhap, hoac tai noi dung bang JavaScript.");
        }

        return [
            'title' => $title,
            'content' => "Nguon: {$url}\n\n" . $content
        ];
    }

    private function isBlockedArticleHost($host) {
        if ($host === '' || in_array($host, ['localhost', '127.0.0.1', '::1'])) {
            return true;
        }

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return !filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
        }

        return false;
    }

    private function downloadUrl($url) {
        if (!function_exists('curl_init')) {
            throw new Exception("PHP cURL chua duoc bat nen khong the doc URL.");
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_USERAGENT => 'AetherMindMidtermBot/1.0',
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => ['Accept: text/html,application/xhtml+xml']
        ]);

        $body = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $error = curl_error($ch);

        if ($body === false || $httpCode < 200 || $httpCode >= 400) {
            throw new Exception("Khong the tai URL (HTTP {$httpCode}). {$error}");
        }

        if ($contentType && stripos($contentType, 'text/html') === false && stripos($contentType, 'application/xhtml') === false) {
            throw new InvalidArgumentException("URL khong tra ve noi dung HTML.");
        }

        if (strlen($body) > 2 * 1024 * 1024) {
            $body = substr($body, 0, 2 * 1024 * 1024);
        }

        return $body;
    }

    private function extractHtmlTitle($html) {
        if (preg_match('/<meta[^>]+property=["\']og:title["\'][^>]+content=["\']([^"\']+)["\']/i', $html, $matches)) {
            return trim(html_entity_decode(strip_tags($matches[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        }

        if (preg_match('/<meta[^>]+content=["\']([^"\']+)["\'][^>]+property=["\']og:title["\']/i', $html, $matches)) {
            return trim(html_entity_decode(strip_tags($matches[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        }

        if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $matches)) {
            return trim(html_entity_decode(strip_tags($matches[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        }

        return '';
    }

    private function extractArticleText($html) {
        if (!class_exists('DOMDocument')) {
            $text = preg_replace('/<(script|style|noscript)[^>]*>.*?<\/\1>/is', ' ', $html);
            return trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
        }

        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        foreach ($xpath->query('//script|//style|//noscript|//nav|//footer|//header|//aside|//form|//svg|//iframe') as $node) {
            if ($node->parentNode) {
                $node->parentNode->removeChild($node);
            }
        }

        $nodes = $xpath->query('//article//p|//main//p|//*[@role="main"]//p|//p');
        $parts = [];

        foreach ($nodes as $node) {
            $text = trim(preg_replace('/\s+/', ' ', $node->textContent));
            if (strlen($text) >= 40) {
                $parts[$text] = true;
            }
        }

        $text = implode("\n\n", array_keys($parts));
        return trim(html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    /**
     * Word DOCX Text Ingestion Parser
     */
    private function parseDocx($filePath) {
        $zip = new ZipArchive();
        if ($zip->open($filePath) === true) {
            if (($index = $zip->locateName('word/document.xml')) !== false) {
                $data = $zip->getFromIndex($index);
                $zip->close();
                
                // Insert newlines for paragraph closures, spaces for tabs, then strip xml tags
                $cleanXml = str_replace(['</w:p>', '</w:r>', '<w:tab/>'], ["\n", "", " "], $data);
                $content = strip_tags($cleanXml);
                return trim($content);
            }
            $zip->close();
        }
        throw new Exception("Không thể mở file DOCX hoặc tìm nội dung XML văn bản.");
    }

    /**
     * CSV Ingestion Parser
     */
    private function parseCsv($filePath) {
        $rows = [];
        if (($handle = fopen($filePath, "r")) !== false) {
            $header = fgetcsv($handle, 1000, ",");
            if ($header !== false) {
                while (($data = fgetcsv($handle, 1000, ",")) !== false) {
                    $row = [];
                    foreach ($header as $i => $colName) {
                        $val = isset($data[$i]) ? trim($data[$i]) : '';
                        if ($val !== '') {
                            $row[] = "**{$colName}**: {$val}";
                        }
                    }
                    if (!empty($row)) {
                        $rows[] = implode(" | ", $row);
                    }
                }
            }
            fclose($handle);
        }

        if (empty($rows)) {
            throw new Exception("Cấu trúc CSV rỗng hoặc không hợp lệ.");
        }

        return implode("\n\n", $rows);
    }
}
