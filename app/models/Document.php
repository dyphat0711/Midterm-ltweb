<?php
/**
 * AetherMind Document Model
 */

class Document extends Model {
    /**
     * Create a document and split it into smart overlapping chunks
     */
    public function create($title, $content) {
        $this->db->beginTransaction();
        try {
            // Count words in complete document (Unicode safe)
            $trimmed = trim($content);
            $wordCount = ($trimmed === '') ? 0 : count(preg_split('/\s+/', $trimmed));

            // 1. Insert parent document record
            $stmt = $this->db->prepare("
                INSERT INTO documents (title, content, word_count)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$title, $content, $wordCount]);
            $documentId = $this->db->lastInsertId();

            // 2. Chunker: Split text into chunks with overlap
            $chunks = $this->chunkText($content, 150, 30); // 150 words per chunk, 30 words overlap

            // 3. Insert chunks into document_chunks
            $chunkStmt = $this->db->prepare("
                INSERT INTO document_chunks (document_id, chunk_index, content, word_count)
                VALUES (?, ?, ?, ?)
            ");

            foreach ($chunks as $index => $chunkContent) {
                $trimmedChunk = trim($chunkContent);
                $chunkWordCount = ($trimmedChunk === '') ? 0 : count(preg_split('/\s+/', $trimmedChunk));
                $chunkStmt->execute([$documentId, $index, $chunkContent, $chunkWordCount]);
            }

            $this->db->commit();
            return $documentId;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Get all documents
     */
    public function getAll() {
        $stmt = $this->db->query("SELECT * FROM documents ORDER BY created_at DESC");
        return $stmt->fetchAll();
    }

    /**
     * Get a specific document
     */
    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM documents WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Get all chunks across all documents (to feed the Vector Visualizer)
     */
    public function getAllChunks() {
        $stmt = $this->db->query("
            SELECT dc.*, d.title as document_title 
            FROM document_chunks dc
            JOIN documents d ON dc.document_id = d.id
            ORDER BY dc.document_id ASC, dc.chunk_index ASC
        ");
        return $stmt->fetchAll();
    }

    /**
     * Delete document and cascade chunks
     */
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM documents WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Smart Text Chunker
     * Splits text by words with a specified window size and overlap
     */
    private function chunkText($text, $chunkSize = 150, $overlap = 30) {
        // Clean multiple white spaces and split by spaces
        $words = preg_split('/\s+/', trim($text));
        $totalWords = count($words);
        $chunks = [];

        if ($totalWords <= $chunkSize) {
            return [$text];
        }

        $i = 0;
        while ($i < $totalWords) {
            // Take a slice of words
            $slice = array_slice($words, $i, $chunkSize);
            $chunks[] = implode(' ', $slice);
            
            // Move pointer forward by (chunkSize - overlap)
            $i += ($chunkSize - $overlap);
            
            // If the remaining words are less than overlap, we can stop to avoid redundant tiny chunks
            if ($totalWords - $i <= $overlap && count($chunks) > 0) {
                break;
            }
        }

        return $chunks;
    }
}
