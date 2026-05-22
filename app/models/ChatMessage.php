<?php
/**
 * AetherMind ChatMessage Model
 */

class ChatMessage extends Model {
    /**
     * Save a new chat message
     */
    public function save($sender, $message, $sentiment = 'Trung tính', $sentimentScore = 0.0, $modelUsed = 'Offline Simulation') {
        $stmt = $this->db->prepare("
            INSERT INTO chat_history (sender, message, sentiment, sentiment_score, model_used)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$sender, $message, $sentiment, $sentimentScore, $modelUsed]);
        return $this->db->lastInsertId();
    }

    /**
     * Retrieve chat history
     */
    public function getHistory($limit = 100) {
        $stmt = $this->db->prepare("
            SELECT * FROM chat_history 
            ORDER BY created_at ASC 
            LIMIT ?
        ");
        // Bind parameters carefully in SQLite for integer limits
        $stmt->bindValue(1, (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Clear all chat logs
     */
    public function clearHistory() {
        return $this->db->exec("DELETE FROM chat_history WHERE model_used != 'System Seed'");
    }

    /**
     * Retrieve global analytics for the dashboard
     */
    public function getAnalyticsSummary() {
        // 1. Total chat count
        $chats = $this->db->query("SELECT COUNT(*) as count FROM chat_history")->fetch()->count;
        
        // 2. Average Sentiment score (converting Pos/Neg to values if not stored, but we store scores!)
        $avgScore = $this->db->query("SELECT AVG(sentiment_score) as avg FROM chat_history WHERE sender = 'user'")->fetch()->avg;
        $avgScore = $avgScore ? round($avgScore, 2) : 0.0;

        // 3. Sentiment breakdown counts
        $breakdown = $this->db->query("
            SELECT sentiment, COUNT(*) as count 
            FROM chat_history 
            WHERE sender = 'user' 
            GROUP BY sentiment
        ")->fetchAll();

        // 4. Model usage counts
        $models = $this->db->query("
            SELECT model_used, COUNT(*) as count 
            FROM chat_history 
            GROUP BY model_used
        ")->fetchAll();

        return [
            'total_chats' => $chats,
            'avg_sentiment' => $avgScore,
            'sentiment_breakdown' => $breakdown,
            'model_usage' => $models
        ];
    }
}
