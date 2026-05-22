<?php
/**
 * AetherMind Portable JSON Datastore
 * Mimics PDO interface to offer 100% plug-and-play environment compatibility
 * (Safeguards against "could not find driver" errors on systems lacking php_pdo_sqlite.dll)
 */

class Database {
    private static $instance = null;
    private $dbFile;
    private $data = [];
    public $lastId = 0;

    private function __construct() {
        // Change file extension to json to reflect the database format
        $this->dbFile = str_replace('.sqlite', '.json', DB_PATH);
        
        // Ensure database directory exists
        $dbDir = dirname($this->dbFile);
        if (!is_dir($dbDir)) {
            mkdir($dbDir, 0777, true);
        }

        // Load data or initialize schema
        if (file_exists($this->dbFile)) {
            $content = file_get_contents($this->dbFile);
            $this->data = json_decode($content, true);
            if (!$this->data || !isset($this->data['chat_history'])) {
                $this->data = $this->getDefaultSchema();
                $this->save();
            }
        } else {
            $this->data = $this->getDefaultSchema();
            $this->save();
        }
    }

    private function getDefaultSchema() {
        return [
            'documents' => [],
            'document_chunks' => [],
            'chat_history' => [
                [
                    'id' => 1,
                    'sender' => 'user',
                    'message' => 'Xin chào AetherMind! Trí tuệ chạy trên thiết bị hoạt động như thế nào?',
                    'sentiment' => 'Tích cực',
                    'sentiment_score' => 0.85,
                    'model_used' => 'System Seed',
                    'created_at' => date('Y-m-d H:i:s', time() - 3600)
                ],
                [
                    'id' => 2,
                    'sender' => 'assistant',
                    'message' => 'Xin chào! Tôi có thể chạy các mạng neural nhẹ như TensorFlow.js ngay trong trình duyệt, đồng thời chuyển các tác vụ suy luận phức tạp qua backend PHP cục bộ.',
                    'sentiment' => 'Tích cực',
                    'sentiment_score' => 0.9,
                    'model_used' => 'System Seed',
                    'created_at' => date('Y-m-d H:i:s', time() - 3595)
                ],
                [
                    'id' => 3,
                    'sender' => 'user',
                    'message' => 'Nghe có vẻ an toàn và nhanh.',
                    'sentiment' => 'Tích cực',
                    'sentiment_score' => 0.95,
                    'model_used' => 'System Seed',
                    'created_at' => date('Y-m-d H:i:s', time() - 3500)
                ],
                [
                    'id' => 4,
                    'sender' => 'assistant',
                    'message' => 'Đúng vậy. Khi giữ API key ở backend và tính cảm xúc cục bộ, hệ thống vừa nhanh vừa hạn chế rò rỉ dữ liệu.',
                    'sentiment' => 'Tích cực',
                    'sentiment_score' => 0.88,
                    'model_used' => 'System Seed',
                    'created_at' => date('Y-m-d H:i:s', time() - 3490)
                ]
            ]
        ];
    }

    public function save() {
        if (file_put_contents($this->dbFile, json_encode($this->data, JSON_PRETTY_PRINT), LOCK_EX) === false) {
            throw new Exception("Lỗi hệ thống: Không thể lưu file database.json. Hãy kiểm tra quyền ghi của thư mục.");
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function resetDemoData() {
        $this->data = $this->getDefaultSchema();
        $this->lastId = 0;
        $this->save();
        return true;
    }

    // --- PDO Interface Mimic Methods ---
    public function beginTransaction() { return true; }
    public function commit() { $this->save(); return true; }
    public function rollBack() { return true; }

    public function lastInsertId() {
        return $this->lastId;
    }

    public function exec($sql) {
        if (stripos($sql, 'DELETE FROM chat_history') !== false) {
            // Clear chats but preserve seeds
            $this->data['chat_history'] = array_filter($this->data['chat_history'], function($chat) {
                return $chat['model_used'] === 'System Seed';
            });
            $this->save();
            return true;
        }
        return false;
    }

    public function query($sql) {
        $stmt = new JSONStatement($this, $sql);
        $stmt->execute();
        return $stmt;
    }

    public function prepare($sql) {
        return new JSONStatement($this, $sql);
    }

    public function &getData() {
        return $this->data;
    }
}

/**
 * Statement class that emulates PDOStatement actions on local JSON arrays
 */
class JSONStatement {
    private $db;
    private $sql;
    private $params = [];
    private $results = [];
    private $currentIndex = 0;

    public function __construct($db, $sql) {
        $this->db = $db;
        // Strip multiple spaces for easier regex parsing
        $this->sql = trim(preg_replace('/\s+/', ' ', $sql));
    }

    public function bindValue($index, $value, $type = null) {
        $this->params[$index - 1] = $value;
    }

    public function execute($params = []) {
        if (!empty($params)) {
            $this->params = $params;
        }

        $data =& $this->db->getData();
        $this->results = [];

        // 1. INSERT INTO chat_history
        if (stripos($this->sql, 'INSERT INTO chat_history') !== false) {
            $id = count($data['chat_history']) > 0 ? max(array_column($data['chat_history'], 'id')) + 1 : 1;
            
            $sender = $this->params[0];
            $message = $this->params[1];
            $sentiment = $this->params[2] ?? 'Trung tính';
            $sentimentScore = isset($this->params[3]) ? floatval($this->params[3]) : 0.5;
            $modelUsed = $this->params[4] ?? 'Offline Simulation';
            $createdAt = $this->params[5] ?? date('Y-m-d H:i:s');

            $record = [
                'id' => $id,
                'sender' => $sender,
                'message' => $message,
                'sentiment' => $sentiment,
                'sentiment_score' => $sentimentScore,
                'model_used' => $modelUsed,
                'created_at' => $createdAt
            ];

            $data['chat_history'][] = $record;
            $this->db->lastId = $id;
            $this->db->save();
        }
        
        // 2. INSERT INTO documents
        elseif (stripos($this->sql, 'INSERT INTO documents') !== false) {
            $id = count($data['documents']) > 0 ? max(array_column($data['documents'], 'id')) + 1 : 1;
            
            $title = $this->params[0];
            $content = $this->params[1];
            $wordCount = isset($this->params[2]) ? (int)$this->params[2] : 0;

            $record = [
                'id' => $id,
                'title' => $title,
                'content' => $content,
                'word_count' => $wordCount,
                'created_at' => date('Y-m-d H:i:s')
            ];

            $data['documents'][] = $record;
            $this->db->lastId = $id;
            $this->db->save();
        }
        
        // 3. INSERT INTO document_chunks
        elseif (stripos($this->sql, 'INSERT INTO document_chunks') !== false) {
            $id = count($data['document_chunks']) > 0 ? max(array_column($data['document_chunks'], 'id')) + 1 : 1;
            
            $documentId = $this->params[0];
            $chunkIndex = $this->params[1];
            $content = $this->params[2];
            $wordCount = isset($this->params[3]) ? (int)$this->params[3] : 0;

            $record = [
                'id' => $id,
                'document_id' => (int)$documentId,
                'chunk_index' => (int)$chunkIndex,
                'content' => $content,
                'word_count' => $wordCount
            ];

            $data['document_chunks'][] = $record;
            $this->db->lastId = $id;
            $this->db->save();
        }
        
        // 4. SELECT * FROM documents WHERE id = ?
        elseif (stripos($this->sql, 'SELECT * FROM documents WHERE id = ?') !== false) {
            $docId = (int)$this->params[0];
            foreach ($data['documents'] as $doc) {
                if ($doc['id'] === $docId) {
                    $this->results[] = (object)$doc;
                    break;
                }
            }
        }
        
        // 5. SELECT * FROM documents
        elseif (stripos($this->sql, 'SELECT * FROM documents') !== false) {
            $docs = $data['documents'];
            // Sort by created_at DESC
            usort($docs, function($a, $b) {
                return strcmp($b['created_at'], $a['created_at']);
            });
            foreach ($docs as $doc) {
                $this->results[] = (object)$doc;
            }
        }
        
        // 6. JOIN document_chunks WITH documents (RAG similarity or full list fetch)
        elseif (stripos($this->sql, 'document_chunks') !== false && stripos($this->sql, 'JOIN documents') !== false) {
            $joined = [];
            foreach ($data['document_chunks'] as $chunk) {
                $title = 'Unknown';
                foreach ($data['documents'] as $doc) {
                    if ($doc['id'] === $chunk['document_id']) {
                        $title = $doc['title'];
                        break;
                    }
                }
                
                $cObj = $chunk;
                $cObj['document_title'] = $title;
                $cObj['title'] = $title; // support ->title attribute
                $joined[] = (object)$cObj;
            }

            // Check if there are search parameters (e.g. for LIKE clauses)
            if (!empty($this->params) && (stripos($this->sql, 'LIKE') !== false || stripos($this->sql, 'WHERE') !== false)) {
                $filtered = [];
                foreach ($joined as $item) {
                    $matched = false;
                    foreach ($this->params as $param) {
                        $keyword = str_replace('%', '', mb_strtolower($param, 'UTF-8'));
                        if (empty($keyword) || mb_strlen($keyword, 'UTF-8') < 2) continue;
                        if (mb_strpos(mb_strtolower($item->content, 'UTF-8'), $keyword, 0, 'UTF-8') !== false) {
                            $matched = true;
                            break;
                        }
                    }
                    if ($matched) {
                        $filtered[] = $item;
                    }
                }
                $joined = $filtered;
            } else {
                // Default sorting only when NOT doing a specific keyword similarity match
                usort($joined, function($a, $b) {
                    if ($a->document_id !== $b->document_id) {
                        return $a->document_id - $b->document_id;
                    }
                    return $a->chunk_index - $b->chunk_index;
                });
            }

            // Check for LIMIT clause
            if (preg_match('/LIMIT\s+(\d+)/i', $this->sql, $limitMatches)) {
                $limitVal = (int)$limitMatches[1];
                $joined = array_slice($joined, 0, $limitVal);
            }

            foreach ($joined as $item) {
                $this->results[] = $item;
            }
        }
        
        // 7. SELECT * FROM chat_history
        elseif (stripos($this->sql, 'SELECT * FROM chat_history') !== false) {
            $limit = isset($this->params[0]) ? (int)$this->params[0] : 100;
            // Get last N records
            $chats = $data['chat_history'];
            $sliced = array_slice($chats, -$limit);
            foreach ($sliced as $chat) {
                $this->results[] = (object)$chat;
            }
        }
        
        // 8. SELECT COUNT(*) as count FROM chat_history
        elseif (stripos($this->sql, 'SELECT COUNT(*) as count FROM chat_history') !== false) {
            $this->results[] = (object)['count' => count($data['chat_history'])];
        }
        
        // 9. SELECT AVG(sentiment_score) as avg FROM chat_history
        elseif (stripos($this->sql, 'SELECT AVG(sentiment_score) as avg FROM chat_history') !== false) {
            $scores = [];
            foreach ($data['chat_history'] as $chat) {
                if ($chat['sender'] === 'user') {
                    $scores[] = (float)$chat['sentiment_score'];
                }
            }
            $avg = count($scores) > 0 ? array_sum($scores) / count($scores) : 0.0;
            $this->results[] = (object)['avg' => $avg];
        }
        
        // 10. SELECT sentiment, COUNT(*) as count FROM chat_history GROUP BY sentiment
        elseif (stripos($this->sql, 'SELECT sentiment, COUNT(*)') !== false) {
            $counts = [];
            foreach ($data['chat_history'] as $chat) {
                if ($chat['sender'] === 'user') {
                    $s = $chat['sentiment'] ?? 'Trung tính';
                    $counts[$s] = ($counts[$s] ?? 0) + 1;
                }
            }
            foreach ($counts as $s => $c) {
                $this->results[] = (object)['sentiment' => $s, 'count' => $c];
            }
        }
        
        // 11. SELECT model_used, COUNT(*) as count FROM chat_history GROUP BY model_used
        elseif (stripos($this->sql, 'SELECT model_used, COUNT(*)') !== false) {
            $counts = [];
            foreach ($data['chat_history'] as $chat) {
                $m = $chat['model_used'] ?? 'Offline Simulation';
                $counts[$m] = ($counts[$m] ?? 0) + 1;
            }
            foreach ($counts as $m => $c) {
                $this->results[] = (object)['model_used' => $m, 'count' => $c];
            }
        }
        
        // 12. SELECT date(created_at) as day, COUNT(*) as count FROM chat_history GROUP BY day
        elseif (stripos($this->sql, 'SELECT date(created_at)') !== false) {
            $counts = [];
            foreach ($data['chat_history'] as $chat) {
                $day = date('Y-m-d', strtotime($chat['created_at']));
                $counts[$day] = ($counts[$day] ?? 0) + 1;
            }
            ksort($counts);
            $sliced = array_slice($counts, 0, 15, true);
            foreach ($sliced as $day => $count) {
                $this->results[] = (object)['day' => $day, 'count' => $count];
            }
        }
        
        // 13. DELETE FROM documents WHERE id = ?
        elseif (stripos($this->sql, 'DELETE FROM documents WHERE id = ?') !== false) {
            $docId = (int)$this->params[0];
            
            // Delete child chunks
            $data['document_chunks'] = array_values(array_filter($data['document_chunks'], function($chunk) use ($docId) {
                return $chunk['document_id'] !== $docId;
            }));
            
            // Delete document record
            $data['documents'] = array_values(array_filter($data['documents'], function($doc) use ($docId) {
                return $doc['id'] !== $docId;
            }));

            $this->db->save();
            $this->results = true;
        }

        return true;
    }

    public function fetchAll() {
        return $this->results;
    }

    public function fetch() {
        if ($this->currentIndex < count($this->results)) {
            $val = $this->results[$this->currentIndex];
            $this->currentIndex++;
            return $val;
        }
        return null;
    }
}
