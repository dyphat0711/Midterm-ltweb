<?php
/**
 * AetherMind Document Controller
 * Coordinates parsing, listing, and deletions of RAG reference materials
 */

class DocumentController extends Controller {
    private $documentModel;

    public function __construct() {
        require_once APP_ROOT . '/models/Document.php';
        $this->documentModel = new Document();
    }

    /**
     * Render the Force-Directed Canvas Visualizer view
     */
    public function visualizer() {
        $data = [
            'title' => 'Không gian vector'
        ];
        $this->view('visualizer/index', $data);
    }

    /**
     * API: List all processed documents
     */
    public function list() {
        try {
            $docs = $this->documentModel->getAll();
            $this->json(['success' => true, 'documents' => $docs]);
        } catch (Exception $e) {
            $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * API: Fetch all chunks across all documents for the Canvas visualizer
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
     * API: Upload a document (via file OR raw copy-paste text input)
     */
    public function upload() {
        // Must be a POST request
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'error' => 'Phương thức không được hỗ trợ'], 405);
        }

        try {
            $title = '';
            $content = '';

            // Case A: File upload present
            if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
                $fileTmpPath = $_FILES['file']['tmp_name'];
                $fileName = $_FILES['file']['name'];
                $fileType = pathinfo($fileName, PATHINFO_EXTENSION);

                // Allow .txt, .md, .csv
                $allowedExtensions = ['txt', 'md', 'csv', 'json'];
                if (!in_array(strtolower($fileType), $allowedExtensions)) {
                    $this->json(['success' => false, 'error' => 'Định dạng file không được hỗ trợ. Vui lòng tải lên .txt, .md hoặc .csv.'], 400);
                }

                $title = pathinfo($fileName, PATHINFO_FILENAME);
                $content = file_get_contents($fileTmpPath);
            } 
            // Case B: Raw text input submitted
            elseif (isset($_POST['content']) && trim($_POST['content']) !== '') {
                $title = isset($_POST['title']) && trim($_POST['title']) !== '' ? trim($_POST['title']) : 'Tài liệu dán nhanh - ' . date('Y-m-d H:i');
                $content = $_POST['content'];
            } 
            else {
                $this->json(['success' => false, 'error' => 'Chưa có file hoặc nội dung được cung cấp.'], 400);
            }

            if (empty($content) || trim($content) === '') {
                $this->json(['success' => false, 'error' => 'Nội dung tài liệu không được để trống.'], 400);
            }

            // Save and chunk document using Model
            $docId = $this->documentModel->create($title, $content);

            $this->json([
                'success' => true,
                'message' => 'Tài liệu đã được xử lý và chia đoạn thành công.',
                'document_id' => $docId,
                'title' => $title
            ]);

        } catch (Exception $e) {
            $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * API: Delete document
     */
    public function delete($id = null) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'DELETE') {
            // For simple HTML forms, we support POST to /api/document/delete/id
            if (!isset($_POST['id']) && $id === null) {
                $this->json(['success' => false, 'error' => 'Phương thức không được hỗ trợ'], 405);
            }
        }

        $docId = $id !== null ? $id : ($_POST['id'] ?? null);

        if ($docId === null) {
            $this->json(['success' => false, 'error' => 'Thiếu ID tài liệu'], 400);
        }

        try {
            $success = $this->documentModel->delete($docId);
            $this->json(['success' => $success, 'message' => 'Đã xóa tài liệu thành công']);
        } catch (Exception $e) {
            $this->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
