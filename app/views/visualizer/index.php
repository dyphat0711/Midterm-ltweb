<!-- GIAO DIEN TRUC QUAN HOA VECTOR SPACE -->
<div class="page-header" style="margin-bottom: 20px;">
    <div>
        <h1 class="page-title">Không gian vector</h1>
        <p class="page-subtitle">Trực quan hóa tương đồng văn bản bằng mô phỏng lực 2D</p>
    </div>
    <div class="pulse-indicator"></div>
</div>

<div class="visualizer-workspace">
    
    <!-- COT TRAI: TAI LIEU VA CHU GIAI -->
    <aside style="display: flex; flex-direction: column; gap: 24px; height: 100%;">
        
        <!-- The chon tai lieu -->
        <div class="glass-card" style="flex-grow: 1; display: flex; flex-direction: column; gap: 15px; overflow-hidden;">
            <h3 class="card-title" style="margin-bottom: 0;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"></path>
                    <path d="m9 12 2 2 4-4"></path>
                </svg>
                <span>Lọc tài liệu</span>
            </h3>
            
            <p style="font-size: 11px; color: #64748b; line-height: 1.4;">
                Chọn tài liệu cần đưa vào không gian tọa độ mô phỏng lực. Các node sẽ được bật tắt động.
            </p>

            <div class="list-container" id="visualizer-document-toggles" style="max-height: 250px;">
                <!-- Danh sach tai lieu duoc tai dong -->
                <div style="text-align: center; color: #64748b; padding: 20px 0; font-style: italic; font-size: 12px;">
                    Đang tải tài liệu...
                </div>
            </div>
            
            <div style="border-top: 1px solid var(--glass-border); padding-top: 15px; font-size: 12px; color: #94a3b8;">
                <h4 style="color: white; font-weight: 600; margin-bottom: 8px; font-family: var(--font-heading);">Chú giải không gian</h4>
                <div style="display: flex; flex-direction: column; gap: 6px;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span style="width: 10px; height: 10px; border-radius: 50%; background: white; box-shadow: 0 0 8px white; display: inline-block;"></span>
                        <span>Node truy vấn</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span style="width: 10px; height: 10px; border-radius: 50%; background: var(--neon-cyan); box-shadow: 0 0 8px var(--neon-cyan); display: inline-block;"></span>
                        <span>Đoạn tài liệu nguồn</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span style="width: 10px; height: 10px; border-radius: 50%; background: var(--neon-purple); box-shadow: 0 0 8px var(--neon-purple); display: inline-block;"></span>
                        <span>Đoạn tài liệu khác</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Widget thong tin node -->
        <div class="glass-card" style="height: 180px; display: flex; flex-direction: column; gap: 10px; font-size: 12px;">
            <h4 style="font-family: var(--font-heading); color: white; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Kiểm tra node</h4>
            <div id="visualizer-node-inspector" style="color: #94a3b8; display: flex; flex-direction: column; gap: 6px; overflow-y: auto;">
                <div style="text-align: center; color: #64748b; padding: 20px 0; font-style: italic;">
                    Di chuột lên một node trên Canvas để xem nội dung đoạn văn bản.
                </div>
            </div>
        </div>

    </aside>

    <!-- COT PHAI: CANVAS HTML5 DONG -->
    <section class="visualizer-canvas-container">
        
        <!-- Thanh phan Canvas -->
        <canvas class="visualizer-canvas" id="physics-canvas"></canvas>

        <!-- Thong tin phu -->
        <div class="visualizer-overlay">
            <div style="font-weight: 600; color: white;">Bộ mô phỏng tọa độ vector</div>
            <div style="color: #64748b; font-size: 10px;" id="visualizer-stats-nodecount">Node: 0 | Lò xo: 0</div>
            <div style="color: #64748b; font-size: 10px; margin-bottom: 8px;">Kéo node để thử lực giảm chấn.</div>
            
            <!-- Zoom Controls (Proposal 3) -->
            <div style="display: flex; align-items: center; gap: 6px; border-top: 1px solid var(--glass-border); padding-top: 8px; margin-top: 2px;">
                <button class="badge cyan" id="zoom-in-btn" style="cursor: pointer; padding: 2px 6px; border: 1px solid rgba(6, 182, 212, 0.3); outline: none;" title="Phóng to">Zoom +</button>
                <button class="badge cyan" id="zoom-out-btn" style="cursor: pointer; padding: 2px 6px; border: 1px solid rgba(6, 182, 212, 0.3); outline: none;" title="Thu nhỏ">Zoom -</button>
                <button class="badge" id="zoom-reset-btn" style="cursor: pointer; padding: 2px 6px; border: 1px solid var(--glass-border); color: #94a3b8; background: transparent; outline: none;" title="Đặt lại về mặc định">Reset</button>
                <span style="color: #94a3b8; font-size: 10px; font-weight: 600; margin-left: 4px;" id="zoom-percent">100%</span>
            </div>
        </div>

        <!-- Hop truy van vector -->
        <div class="visualizer-search-box">
            <input type="text" class="chat-input" id="visualizer-search-input" placeholder="Nhập từ khóa (ví dụ: 'intelligence', 'security') để tính lực hút..." autocomplete="off">
            <button class="send-btn" id="visualizer-search-btn" style="padding: 10px 18px;">
                <span>Truy vấn</span>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
            </button>
        </div>

    </section>
    
</div>
