<!-- GIAO DIEN BANG DIEU KHIEN -->
<div class="page-header">
    <div>
        <h1 class="page-title">Bảng điều khiển</h1>
        <p class="page-subtitle">Theo dõi hệ thống AetherMind và kho tài liệu RAG</p>
    </div>
    <button class="settings-btn" id="reset-demo-btn" style="width: auto; padding: 8px 14px; border-color: rgba(245, 158, 11, 0.25); color: var(--neon-amber);">
        Reset demo
    </button>
</div>

<!-- 4 THE THONG KE -->
<div class="dashboard-grid">
    <div class="glass-card stat-card">
        <div class="stat-header">
            <span>Hội thoại</span>
            <div class="stat-icon purple">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                </svg>
            </div>
        </div>
        <div class="stat-value" id="stats-total-chats"><?php echo $total_chats; ?></div>
        <div class="stat-desc">Tin nhắn lưu cục bộ</div>
    </div>

    <div class="glass-card stat-card">
        <div class="stat-header">
            <span>Cảm xúc TB</span>
            <div class="stat-icon blue">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <path d="M8 14s1.5 2 4 2 4-2 4-2"></path>
                    <line x1="9" y1="9" x2="9.01" y2="9"></line>
                    <line x1="15" y1="9" x2="15.01" y2="9"></line>
                </svg>
            </div>
        </div>
        <div class="stat-value" id="stats-avg-sentiment" style="color: <?php echo ($avg_sentiment >= 0.7) ? 'var(--neon-emerald)' : 'var(--neon-amber)'; ?>;">
            <?php echo ($avg_sentiment * 100); ?>%
        </div>
        <div class="stat-desc">Tính bằng TF.js trên trình duyệt</div>
    </div>

    <div class="glass-card stat-card">
        <div class="stat-header">
            <span>Tài liệu tham chiếu</span>
            <div class="stat-icon emerald">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                    <polyline points="10 9 9 9 8 9"></polyline>
                </svg>
            </div>
        </div>
        <div class="stat-value" id="stats-total-docs"><?php echo $total_documents; ?></div>
        <div class="stat-desc">Đã đọc và chia thành đoạn</div>
    </div>

    <div class="glass-card stat-card">
        <div class="stat-header">
            <span>Từ đã xử lý</span>
            <div class="stat-icon amber">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 20h9"></path>
                    <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>
                </svg>
            </div>
        </div>
        <div class="stat-value" id="stats-total-words"><?php echo number_format($total_words); ?></div>
        <div class="stat-desc">Tổng số từ trong bộ nhớ</div>
    </div>
</div>

<!-- KHONG GIAN LAM VIEC HAI COT -->
<div class="two-column-layout">
    
    <!-- COT TRAI: DANH SACH TAI LIEU VA TAI LEN -->
    <div class="glass-card" style="display: flex; flex-direction: column; gap: 20px;">
        <h3 class="card-title">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                <polyline points="14 2 14 8 20 8"></polyline>
            </svg>
            <span>Thư viện tài liệu RAG</span>
        </h3>

        <!-- Khu vuc tai len -->
        <div class="form-file-upload" id="dropzone">
            <input type="file" id="dashboard-file-input" style="display: none;" accept=".txt,.md,.csv,.json,.pdf,.docx">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                <polyline points="17 8 12 3 7 8"></polyline>
                <line x1="12" y1="3" x2="12" y2="15"></line>
            </svg>
            <h4 style="margin-bottom: 4px; font-weight: 600; color: white;">Kéo thả tài liệu tham chiếu</h4>
            <p style="font-size: 12px; color: #64748b;">Hỗ trợ TXT, MD, CSV, PDF hoặc DOCX (tự động chia đoạn)</p>
        </div>

        <!-- Danh sach tai lieu -->
        <div class="list-container" id="dashboard-document-list">
            <?php if (empty($documents)): ?>
                <div style="text-align: center; color: #64748b; padding: 40px 0; font-style: italic;">
                    Chưa có tài liệu nào được lập chỉ mục. Hãy tải file lên hoặc dán văn bản bên dưới.
                </div>
            <?php else: ?>
                <?php foreach ($documents as $doc): ?>
                    <div class="list-item" id="doc-item-<?php echo $doc->id; ?>">
                        <div class="item-info">
                            <span class="item-title"><?php echo htmlspecialchars($doc->title); ?></span>
                            <span class="item-meta">
                                <?php echo $doc->word_count; ?> từ - Đã lập chỉ mục <?php echo date('M d, Y H:i', strtotime($doc->created_at)); ?>
                            </span>
                        </div>
                        <div style="display: flex; gap: 8px; align-items: center;">
                            <span class="badge purple">SQLite</span>
                            <button class="modal-close delete-doc-btn" data-id="<?php echo $doc->id; ?>" style="color: var(--neon-rose); cursor: pointer;" title="Xóa tài liệu">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="3 6 5 6 21 6"></polyline>
                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div style="border-top: 1px solid var(--glass-border); padding-top: 15px;">
            <h4 style="font-family: var(--font-heading); font-size: 14px; font-weight: 600; color: white; margin-bottom: 10px;">Lập chỉ mục nhanh</h4>
            <div style="background: rgba(6, 182, 212, 0.06); border: 1px solid rgba(6, 182, 212, 0.16); border-radius: 10px; padding: 12px; margin-bottom: 14px;">
                <div style="font-family: var(--font-heading); font-size: 12px; font-weight: 700; color: var(--neon-cyan); margin-bottom: 8px; text-transform: uppercase;">
                    Nhập URL bài báo
                </div>
                <div class="form-group" style="margin-bottom: 10px;">
                    <input type="url" class="form-input" id="article-url-input" placeholder="https://example.com/bai-bao-can-lap-chi-muc" style="width: 100%;">
                </div>
                <div style="display: flex; justify-content: space-between; gap: 10px; align-items: center;">
                    <span style="font-size: 11px; color: #64748b; line-height: 1.4;">Hỗ trợ trang HTML công khai. Trang chặn crawler hoặc cần đăng nhập có thể không đọc được.</span>
                    <button class="training-btn" id="article-url-submit" style="width: auto; padding: 8px 16px; white-space: nowrap;">
                        Lập chỉ mục URL
                    </button>
                </div>
            </div>
            <div class="form-group" style="margin-bottom: 10px;">
                <input type="text" class="form-input" id="quick-doc-title" placeholder="Tiêu đề tài liệu (ví dụ: Đặc tả sản phẩm)" style="width: 100%;">
            </div>
            <div class="form-group" style="margin-bottom: 10px;">
                <textarea class="form-input" id="quick-doc-content" rows="3" placeholder="Dán văn bản tại đây... AetherMind sẽ tự động chia thành các đoạn có chồng lắp." style="width: 100%; resize: none; font-family: var(--font-body); font-size: 13px;"></textarea>
            </div>
            <button class="training-btn" id="quick-doc-submit" style="width: auto; float: right; padding: 8px 16px;">
                Lập chỉ mục
            </button>
        </div>
    </div>

    <!-- COT PHAI: THONG KE HE THONG -->
    <div class="glass-card" style="display: flex; flex-direction: column; gap: 20px;">
        <h3 class="card-title">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="20" x2="18" y2="10"></line>
                <line x1="12" y1="20" x2="12" y2="4"></line>
                <line x1="6" y1="20" x2="6" y2="14"></line>
            </svg>
            <span>Chỉ số hệ thống lai</span>
        </h3>

        <!-- Bieu do 1: phan bo cam xuc -->
        <div style="background: rgba(0, 0, 0, 0.15); border: 1px solid var(--glass-border); border-radius: 12px; padding: 20px;">
            <h4 style="font-family: var(--font-heading); font-size: 13px; font-weight: 600; color: #94a3b8; text-transform: uppercase; margin-bottom: 12px; letter-spacing: 0.5px;">
                Thống kê cảm xúc văn bản
            </h4>
            <div id="sentiment-chart-container" style="height: 140px; display: flex; align-items: flex-end; justify-content: space-around; padding-bottom: 10px; position: relative;">
                <!-- SVGs or dynamic bars will be loaded here via dashboard telemetry scripts! -->
                <div style="text-align: center; width: 30%;">
                    <div style="height: 80px; width: 24px; background: var(--neon-emerald); margin: 0 auto; border-radius: 4px; box-shadow: 0 0 10px var(--neon-emerald); filter: opacity(0.8);"></div>
                    <span style="font-size: 11px; color: #64748b; display: block; margin-top: 6px;">Tích cực</span>
                </div>
                <div style="text-align: center; width: 30%;">
                    <div style="height: 40px; width: 24px; background: var(--neon-amber); margin: 0 auto; border-radius: 4px; box-shadow: 0 0 10px var(--neon-amber); filter: opacity(0.8);"></div>
                    <span style="font-size: 11px; color: #64748b; display: block; margin-top: 6px;">Trung tính</span>
                </div>
                <div style="text-align: center; width: 30%;">
                    <div style="height: 20px; width: 24px; background: var(--neon-rose); margin: 0 auto; border-radius: 4px; box-shadow: 0 0 10px var(--neon-rose); filter: opacity(0.8);"></div>
                    <span style="font-size: 11px; color: #64748b; display: block; margin-top: 6px;">Tiêu cực</span>
                </div>
            </div>
        </div>

        <!-- Bieu do 2: phan bo mo hinh -->
        <div style="background: rgba(0, 0, 0, 0.15); border: 1px solid var(--glass-border); border-radius: 12px; padding: 20px;">
            <h4 style="font-family: var(--font-heading); font-size: 13px; font-weight: 600; color: #94a3b8; text-transform: uppercase; margin-bottom: 12px; letter-spacing: 0.5px;">
                Công cụ AI đang sử dụng
            </h4>
            <div id="model-usage-container" style="display: flex; flex-direction: column; gap: 10px;">
                <div>
                    <div style="display: flex; justify-content: space-between; font-size: 12px; color: #e2e8f0; margin-bottom: 4px;">
                        <span>TensorFlow.js (trình duyệt)</span>
                        <span id="pct-tfjs">100%</span>
                    </div>
                    <div style="height: 6px; width: 100%; background: rgba(255,255,255,0.05); border-radius: 3px;">
                        <div style="height: 100%; width: 100%; background: linear-gradient(to right, var(--neon-cyan), var(--neon-purple)); border-radius: 3px; box-shadow: var(--glow-cyan);"></div>
                    </div>
                </div>
                <div>
                    <div style="display: flex; justify-content: space-between; font-size: 12px; color: #e2e8f0; margin-bottom: 4px;">
                        <span>Google Gemini (proxy qua backend)</span>
                        <span id="pct-gemini">Chế độ lai</span>
                    </div>
                    <div style="height: 6px; width: 100%; background: rgba(255,255,255,0.05); border-radius: 3px;">
                        <div style="height: 100%; width: 70%; background: var(--neon-purple); border-radius: 3px; box-shadow: var(--glow-purple);"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Thong tin kien truc nhanh -->
        <div class="ml-widget" style="padding: 16px; font-size: 12px; color: #94a3b8; line-height: 1.5; border-color: rgba(168, 85, 247, 0.2);">
            <div style="display: flex; gap: 8px; align-items: center; font-weight: 600; color: white; margin-bottom: 6px;">
                <span class="status-dot"></span>
                <span>Kiến trúc AI lai đang hoạt động</span>
            </div>
            Tất cả tài liệu được đọc và chia đoạn trong backend PHP MVC cục bộ, lưu vào datastore JSON di động, rồi được Canvas Vector Visualizer tải lên để mô phỏng truy xuất tương đồng offline.
        </div>
    </div>
</div>
