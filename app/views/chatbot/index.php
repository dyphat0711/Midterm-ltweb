<!-- GIAO DIEN KHONG GIAN CHAT -->
<div class="page-header" style="margin-bottom: 20px;">
    <div>
        <h1 class="page-title">Không gian AI</h1>
        <p class="page-subtitle">Theo dõi cảm xúc khi nhập và proxy LLM lai an toàn</p>
    </div>
    <button class="settings-btn" id="clear-chat-btn" style="width: auto; padding: 6px 12px; border-color: rgba(244, 63, 94, 0.2); color: var(--neon-rose); display: flex; align-items: center; gap: 6px;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="3 6 5 6 21 6"></polyline>
            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
        </svg>
        <span>Xóa lịch sử</span>
    </button>
</div>

<div class="chat-workspace">
    
    <!-- BANG TRAI: SANDBOX TENSORFLOW.JS VA HUAN LUYEN NN -->
    <aside class="ml-sandbox-panel">
        
        <!-- WIDGET 1: CAM XUC KHI NHAP TREN THIET BI -->
        <div class="glass-card ml-widget">
            <div class="ml-widget-title">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <path d="M8 14s1.5 2 4 2 4-2 4-2"></path>
                    <line x1="9" y1="9" x2="9.01" y2="9"></line>
                    <line x1="15" y1="9" x2="15.01" y2="9"></line>
                </svg>
                <span>Cảm xúc cục bộ</span>
            </div>
            
            <div class="sentiment-gauge-container">
                <span class="sentiment-label" id="live-sentiment-lbl">
                    <span>Trung tính</span>
                </span>
                
                <div class="gauge-track">
                    <div class="gauge-fill" id="live-sentiment-fill"></div>
                    <div class="gauge-marker" id="live-sentiment-marker" style="left: 50%;"></div>
                </div>
                
                <div style="width: 100%; display: flex; justify-content: space-between; font-size: 10px; color: #64748b; font-weight: 600;">
                    <span>TIÊU CỰC</span>
                    <span id="live-sentiment-val">0.50</span>
                    <span>TÍCH CỰC</span>
                </div>
            </div>
            <p style="font-size: 11px; color: #64748b; line-height: 1.4; margin-top: 4px;">
                *Phân tích trực tiếp khi bạn nhập bằng mạng bag-of-words chạy trong trình duyệt với TensorFlow.js.*
            </p>
        </div>

        <!-- WIDGET 2: HUAN LUYEN MANG NEURAL TREN TRINH DUYET -->
        <div class="glass-card ml-widget">
            <div class="ml-widget-title" style="color: var(--neon-cyan);">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="2" y="2" width="20" height="8" rx="2" ry="2"></rect>
                    <rect x="2" y="14" width="20" height="8" rx="2" ry="2"></rect>
                    <line x1="6" y1="6" x2="6.01" y2="6"></line>
                    <line x1="6" y1="18" x2="6.01" y2="18"></line>
                </svg>
                <span>Huấn luyện NN</span>
            </div>
            
            <div style="display: flex; flex-direction: column; gap: 8px;">
                <p style="font-size: 11px; color: #94a3b8; line-height: 1.4; margin-bottom: 6px;">
                    Huấn luyện mạng neural nhỏ ngay trên trình duyệt để phân loại truy vấn.
                </p>

                <!-- Seeds telemetry -->
                <div style="font-size: 10px; color: #64748b; background: rgba(0,0,0,0.15); border: 1px solid var(--glass-border); border-radius: 6px; padding: 8px;">
                    <strong>Mẫu huấn luyện có sẵn:</strong><br>
                    - "awesome build product" -> Tích cực (1)<br>
                    - "very bad slow crash" -> Tiêu cực (0)<br>
                    - "great job love it" -> Tích cực (1)<br>
                    - "terrible error broken" -> Tiêu cực (0)
                </div>

                <div class="training-field-row" style="margin-top: 6px;">
                    <span class="training-label">Epoch (vòng huấn luyện)</span>
                    <input type="number" class="training-input" id="training-epochs" value="60" min="10" max="300">
                </div>

                <button class="training-btn" id="start-training-btn">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polygon points="5 3 19 12 5 21 5 3"></polygon>
                    </svg>
                    <span>Huấn luyện mạng</span>
                </button>

                <!-- Real-time Training feedback graph -->
                <div class="training-progress-chart" id="training-chart">
                    <div style="position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; font-size: 11px; color: #64748b;" id="chart-status-text">
                        Network Idle. Awaiting training...
                    </div>
                    <!-- SVG hien thi duong loss theo thoi gian thuc khi huan luyen -->
                    <svg id="training-svg" style="width:100%; height:100%; display:none;"></svg>
                </div>
            </div>
        </div>
        
    </aside>

    <!-- BANG PHAI: KHUNG HOI THOAI -->
    <section class="chat-panel">
        <div class="chat-box">
            
            <!-- CHON TAC TU -->
            <div class="agent-selector-header glass-card">
                <div class="agent-selector-title">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                    <span>TÁC TỬ ĐANG HOẠT ĐỘNG</span>
                </div>
                
                <div class="agent-cards-row">
                    <!-- Agent 1: AetherMind -->
                    <div class="agent-card active" data-agent="general" id="agent-card-general">
                        <div class="agent-avatar general">A</div>
                        <div class="agent-info">
                            <span class="agent-name">AetherMind</span>
                            <span class="agent-role">Tổng hợp chính</span>
                        </div>
                    </div>

                    <!-- Agent 2: Doc-Analyst -->
                    <div class="agent-card" data-agent="analyst" id="agent-card-analyst">
                        <div class="agent-avatar analyst">D</div>
                        <div class="agent-info">
                            <span class="agent-name">Doc-Analyst</span>
                            <span class="agent-role">Khai thác tri thức</span>
                        </div>
                    </div>

                    <!-- Agent 3: Sentiment Auditor -->
                    <div class="agent-card" data-agent="auditor" id="agent-card-auditor">
                        <div class="agent-avatar auditor">S</div>
                        <div class="agent-info">
                            <span class="agent-name">Kiểm toán cảm xúc</span>
                            <span class="agent-role">Phân tích sắc thái</span>
                        </div>
                    </div>
                </div>

                <!-- Cong tac nhieu tac tu -->
                <div class="collab-toggle-container">
                    <label class="collab-switch">
                        <input type="checkbox" id="collab-mode-checkbox">
                        <span class="collab-slider"></span>
                    </label>
                    <span class="collab-label">
                        <strong style="color: var(--neon-purple);">Chế độ cộng tác</strong>
                        <span style="display:block; font-size:10px; color:#64748b;">Cho 3 tác tử lần lượt phản hồi</span>
                    </span>
                </div>
            </div>

            <!-- Danh sach tin nhan -->
            <div class="chat-messages" id="chat-messages-container">
                <!-- System Loader -->
                <div style="text-align: center; color: #64748b; padding: 40px 0;" id="chat-init-loader">
                    <div class="pulse-indicator" style="margin-bottom: 12px;"></div>
                    <p style="font-size: 12px;">Đang mở kết nối dữ liệu cục bộ...</p>
                </div>
            </div>

            <!-- Vung nhap -->
            <div class="chat-input-area">
                <div class="input-container">
                    <input type="text" class="chat-input" id="chat-input-field" placeholder="Nhập tin nhắn... cảm xúc được chấm điểm trực tiếp trên trình duyệt" autocomplete="off">
                    <button class="send-btn" id="chat-send-btn">
                        <span>Gửi</span>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="22" y1="2" x2="11" y2="13"></line>
                            <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                        </svg>
                    </button>
                </div>
                
                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 10px; font-size: 11px; color: #64748b; font-weight: 500;">
                    <div style="display: flex; gap: 15px; align-items: center;">
                        <span style="display: flex; align-items: center; gap: 4px;">
                            <span class="status-dot" id="rag-indicator-dot"></span>
                            <span id="rag-indicator-text">RAG đang bật (tự tìm ngữ cảnh)</span>
                        </span>
                        <span id="model-mode-text" style="color: var(--neon-purple); font-weight: 600;">Chế độ mô phỏng</span>
                    </div>
                    <div>
                        <span>Kết nối API Proxy: </span><span id="proxy-status" style="color: var(--neon-emerald);">An toàn</span>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
</div>
