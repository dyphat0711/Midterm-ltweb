<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo isset($title) ? $title . ' | ' . SITE_NAME : SITE_NAME; ?></title>
    <meta name="description" content="AetherMind: Không gian tri thức thông minh với Machine Learning trên trình duyệt và API LLM tùy chọn.">
    <meta name="keywords" content="AetherMind, AI, RAG, Machine Learning, TensorFlow.js, PHP MVC, Intelligent Web App">
    <meta name="author" content="AetherMind Team">
    
    <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="<?php echo isset($title) ? $title . ' | ' . SITE_NAME : SITE_NAME; ?>">
    <meta property="og:description" content="AetherMind: Không gian tri thức thông minh với Machine Learning trên trình duyệt.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo URL_ROOT; ?>">
    
    <!-- Twitter Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo isset($title) ? $title . ' | ' . SITE_NAME : SITE_NAME; ?>">
    <meta name="twitter:description" content="AetherMind: Không gian tri thức thông minh với Machine Learning trên trình duyệt.">

    <link rel="stylesheet" href="<?php echo URL_ROOT; ?>/css/style.css">
    <!-- Thu vien TensorFlow.js tu CDN -->
    <script src="https://cdn.jsdelivr.net/npm/@tensorflow/tfjs@4.20.0/dist/tf.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@tensorflow-models/universal-sentence-encoder@1.3.3/dist/universal-sentence-encoder.min.js"></script>
    <!-- Thu vien PDF.js de doc tai lieu PDF tren trinh duyet -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.4.120/pdf.min.js"></script>
</head>
<body>
    <div class="app-container">
        
        <!-- THANH DIEU HUONG BEN TRAI -->
        <aside class="sidebar">
            <div class="brand-section">
                <div class="brand-logo">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polygon points="12 2 2 7 12 12 22 7 12 2"></polygon>
                        <polyline points="2 17 12 22 22 17"></polyline>
                        <polyline points="2 12 12 17 22 12"></polyline>
                    </svg>
                </div>
                <span class="brand-name">AetherMind</span>
            </div>

            <nav class="sidebar-nav">
                <!-- Trang hien tai duoc danh dau dua tren title -->
                <a href="<?php echo URL_ROOT; ?>/" class="nav-item <?php echo ($title === 'Dashboard' || $title === 'Bảng điều khiển') ? 'active' : ''; ?>" id="nav-dashboard" aria-label="Đến trang bảng điều khiển">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <rect x="3" y="3" width="7" height="9"></rect>
                        <rect x="14" y="3" width="7" height="5"></rect>
                        <rect x="14" y="12" width="7" height="9"></rect>
                        <rect x="3" y="16" width="7" height="5"></rect>
                    </svg>
                    <span>Bảng điều khiển</span>
                </a>
                
                <a href="<?php echo URL_ROOT; ?>/chat" class="nav-item <?php echo ($title === 'Intelligent Workspace' || $title === 'Không gian AI') ? 'active' : ''; ?>" id="nav-chat" aria-label="Mở không gian AI">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                    </svg>
                    <span>Không gian AI</span>
                </a>
                
                <a href="<?php echo URL_ROOT; ?>/visualizer" class="nav-item <?php echo ($title === 'Vector Space Visualizer' || $title === 'Không gian vector') ? 'active' : ''; ?>" id="nav-visualizer" aria-label="Mở không gian Vector">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="2" y1="12" x2="22" y2="12"></line>
                        <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
                    </svg>
                    <span>Không gian vector</span>
                </a>
            </nav>

            <div class="sidebar-footer">
                <button class="settings-btn" id="open-settings-btn" aria-label="Mở bảng cài đặt hệ thống">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="3"></circle>
                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                    </svg>
                    <span>Cài đặt API</span>
                </button>
                
                <div class="model-badge" id="sidebar-active-model">
                    <span class="status-dot"></span>
                    <span id="sidebar-active-model-text">ML cục bộ TF.js</span>
                </div>
            </div>
        </aside>

        <!-- NOI DUNG CHINH -->
        <main class="main-content">
            <?php if (defined('MYSQL_FALLBACK_ACTIVE') && MYSQL_FALLBACK_ACTIVE): ?>
                <div class="db-fallback-banner">
                    <span class="banner-icon">⚠️</span>
                    <span class="banner-text">
                        <strong>Chế độ dự phòng hoạt động:</strong> Không thể kết nối tới cơ sở dữ liệu MySQL trên XAMPP (Cổng 3306). Hệ thống đã tự động chuyển sang cơ sở dữ liệu di động **JSON**. Hãy khởi động MySQL trên XAMPP và tải lại trang để đồng bộ và chuyển về cơ sở dữ liệu hiệu năng cao!
                    </span>
                </div>
            <?php endif; ?>
            <?php echo $content; ?>
        </main>
        
    </div>

    <!-- HOP THOAI CAI DAT TOAN CUC -->
    <div class="modal-overlay" id="settings-modal">
        <div class="modal-card glass-card">
            <div class="modal-header">
                <h3 class="card-title" style="margin-bottom: 0;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="3"></circle>
                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                    </svg>
                    <span>Cấu hình hệ thống</span>
                </h3>
                <button class="modal-close" id="close-settings-btn" aria-label="Đóng cài đặt">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
            
            <div class="form-group">
                <label class="form-label">Chế độ mô hình AI</label>
                <select class="form-input" id="settings-model-mode" style="width: 100%;">
                    <option value="Offline Simulation">ML cục bộ offline (TensorFlow.js + mô phỏng)</option>
                    <option value="Gemini">AI cloud lai (Google Gemini Flash)</option>
                </select>
            </div>

            <div class="form-group" id="gemini-key-group" style="display: none;">
                <label class="form-label">Google Gemini API Key</label>
                <input type="password" class="form-input" id="settings-gemini-key" placeholder="AIzaSy..." style="width: 100%;">
                <small style="color: #64748b; font-size: 11px; margin-top: 4px; display: block;">
                    Khóa của bạn được lưu trong phiên trình duyệt và chỉ gửi đến backend PHP cục bộ khi gọi Gemini.
                </small>
            </div>

            <div class="form-group">
                <label class="form-label">Xử lý RAG</label>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <input type="checkbox" id="settings-rag-enabled" checked style="width: 18px; height: 18px; accent-color: var(--neon-cyan);">
                    <span style="font-size: 13px; color: #94a3b8;">Tìm các đoạn ngữ cảnh phù hợp khi chat</span>
                </div>
            </div>

            <button class="training-btn" id="save-settings-btn" style="margin-top: 10px;">
                Lưu cấu hình
            </button>
        </div>
    </div>

    <!-- CAC FILE JAVASCRIPT CHINH -->
    <!-- Khai bao URL goc cua ung dung cho JavaScript -->
    <script>
        const URL_ROOT = '<?php echo URL_ROOT; ?>';
    </script>
    <script src="<?php echo URL_ROOT; ?>/js/tfjs-helper.js"></script>
    <script src="<?php echo URL_ROOT; ?>/js/rag-engine.js"></script>
    <script src="<?php echo URL_ROOT; ?>/js/visualizer.js"></script>
    <script src="<?php echo URL_ROOT; ?>/js/app.js"></script>
</body>
</html>
