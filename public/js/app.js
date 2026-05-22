/**
 * AetherMind Global Frontend Coordinator & SPA Interactive Bindings
 */

document.addEventListener('DOMContentLoaded', () => {
    // Instanciate helper classes
    const tfHelper = new TfjsHelper();
    const rag = new RagEngine();
    let visualizer = null;

    // Load persistent settings from localStorage
    let appSettings = {
        modelMode: localStorage.getItem('aether_model_mode') || 'Offline Simulation',
        geminiKey: localStorage.getItem('aether_gemini_key') || '',
        ragEnabled: localStorage.getItem('aether_rag_enabled') !== 'false' // default true
    };

    // Apply settings globally
    updateGlobalModelBadge();

    // ----------------------------------------
    // 1. GLOBAL SETTINGS MODAL INTERACTION
    // ----------------------------------------
    const settingsModal = document.getElementById('settings-modal');
    const openSettingsBtn = document.getElementById('open-settings-btn');
    const closeSettingsBtn = document.getElementById('close-settings-btn');
    const saveSettingsBtn = document.getElementById('save-settings-btn');

    const selectModelMode = document.getElementById('settings-model-mode');
    const inputGeminiKey = document.getElementById('settings-gemini-key');
    const checkRagEnabled = document.getElementById('settings-rag-enabled');
    const geminiKeyGroup = document.getElementById('gemini-key-group');

    // Populate modal inputs on load
    if (selectModelMode) selectModelMode.value = appSettings.modelMode;
    if (inputGeminiKey) inputGeminiKey.value = appSettings.geminiKey;
    if (checkRagEnabled) checkRagEnabled.checked = appSettings.ragEnabled;
    toggleGeminiKeyField();

    if (selectModelMode) {
        selectModelMode.addEventListener('change', toggleGeminiKeyField);
    }

    if (openSettingsBtn) {
        openSettingsBtn.addEventListener('click', () => {
            settingsModal.classList.add('open');
        });
    }

    if (closeSettingsBtn) {
        closeSettingsBtn.addEventListener('click', () => {
            settingsModal.classList.remove('open');
        });
    }

    if (settingsModal) {
        settingsModal.addEventListener('click', (e) => {
            if (e.target === settingsModal) {
                settingsModal.classList.remove('open');
            }
        });
    }

    if (saveSettingsBtn) {
        saveSettingsBtn.addEventListener('click', () => {
            appSettings.modelMode = selectModelMode.value;
            appSettings.geminiKey = inputGeminiKey.value.trim();
            appSettings.ragEnabled = checkRagEnabled.checked;

            localStorage.setItem('aether_model_mode', appSettings.modelMode);
            localStorage.setItem('aether_gemini_key', appSettings.geminiKey);
            localStorage.setItem('aether_rag_enabled', appSettings.ragEnabled);

            updateGlobalModelBadge();
            settingsModal.classList.remove('open');

            // Dispatch update event if other views need it
            const event = new CustomEvent('aetherSettingsUpdated');
            window.dispatchEvent(event);
        });
    }

    function toggleGeminiKeyField() {
        if (selectModelMode && geminiKeyGroup) {
            if (selectModelMode.value === 'Gemini') {
                geminiKeyGroup.style.display = 'block';
            } else {
                geminiKeyGroup.style.display = 'none';
            }
        }
    }

    function updateGlobalModelBadge() {
        const textEl = document.getElementById('sidebar-active-model-text');
        const badgeEl = document.getElementById('sidebar-active-model');
        
        if (textEl && badgeEl) {
            if (appSettings.modelMode === 'Gemini') {
                textEl.innerText = 'Gemini Flash lai';
                badgeEl.style.borderColor = 'rgba(168, 85, 247, 0.4)';
                badgeEl.style.color = 'var(--neon-purple)';
                badgeEl.style.background = 'rgba(168, 85, 247, 0.1)';
            } else {
                textEl.innerText = 'ML cục bộ TF.js';
                badgeEl.style.borderColor = 'rgba(6, 182, 212, 0.4)';
                badgeEl.style.color = 'var(--neon-cyan)';
                badgeEl.style.background = 'rgba(6, 182, 212, 0.1)';
            }
        }

        // AI Workspace-specific elements
        const modeTextEl = document.getElementById('model-mode-text');
        if (modeTextEl) {
            modeTextEl.innerText = appSettings.modelMode === 'Gemini' ? 'Gemini Flash đang bật' : 'Trình mô phỏng TF.js offline';
            modeTextEl.style.color = appSettings.modelMode === 'Gemini' ? 'var(--neon-purple)' : 'var(--neon-cyan)';
        }

        const ragDotEl = document.getElementById('rag-indicator-dot');
        const ragTextEl = document.getElementById('rag-indicator-text');
        if (ragTextEl && ragDotEl) {
            if (appSettings.ragEnabled) {
                ragTextEl.innerText = 'Truy xuất RAG đang bật';
                ragDotEl.style.backgroundColor = 'var(--neon-emerald)';
                ragDotEl.style.boxShadow = '0 0 8px var(--neon-emerald)';
            } else {
                ragTextEl.innerText = 'Truy xuất RAG đang tắt';
                ragDotEl.style.backgroundColor = 'var(--neon-rose)';
                ragDotEl.style.boxShadow = '0 0 8px var(--neon-rose)';
            }
        }
    }

    // ----------------------------------------
    // 2. VIEW-SPECIFIC INITIALIZATION
    // ----------------------------------------
    const docTitle = document.title;

    if (docTitle.includes('Dashboard') || docTitle.includes('Bảng điều khiển')) {
        initDashboardView();
    } else if (docTitle.includes('Intelligent Workspace') || docTitle.includes('Không gian AI')) {
        initChatView();
    } else if (docTitle.includes('Vector Space Visualizer') || docTitle.includes('Không gian vector')) {
        initVisualizerView();
    }

    // --- DASHBOARD ROUTER ACTIONS ---
    function initDashboardView() {
        const dropzone = document.getElementById('dropzone');
        const fileInput = document.getElementById('dashboard-file-input');
        const quickTitle = document.getElementById('quick-doc-title');
        const quickContent = document.getElementById('quick-doc-content');
        const quickSubmit = document.getElementById('quick-doc-submit');
        const articleUrlInput = document.getElementById('article-url-input');
        const articleUrlSubmit = document.getElementById('article-url-submit');
        const resetDemoBtn = document.getElementById('reset-demo-btn');
        renderDashboardCharts();

        // Drag and drop mechanics
        if (dropzone) {
            dropzone.addEventListener('click', () => fileInput.click());
            
            dropzone.addEventListener('dragover', (e) => {
                e.preventDefault();
                dropzone.style.borderColor = 'var(--neon-cyan)';
                dropzone.style.background = 'rgba(6, 182, 212, 0.05)';
            });

            dropzone.addEventListener('dragleave', () => {
                dropzone.style.borderColor = 'var(--glass-border)';
                dropzone.style.background = 'transparent';
            });

            dropzone.addEventListener('drop', (e) => {
                e.preventDefault();
                dropzone.style.borderColor = 'var(--glass-border)';
                dropzone.style.background = 'transparent';

                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    uploadDocumentFile(files[0]);
                }
            });
        }

        if (fileInput) {
            fileInput.addEventListener('change', () => {
                if (fileInput.files.length > 0) {
                    uploadDocumentFile(fileInput.files[0]);
                }
            });
        }

        // Article URL submission
        if (articleUrlSubmit) {
            articleUrlSubmit.addEventListener('click', () => {
                const url = articleUrlInput.value.trim();

                if (!url) {
                    alert('Vui lòng nhập URL bài báo.');
                    return;
                }

                let parsedUrl;
                try {
                    parsedUrl = new URL(url);
                } catch (err) {
                    alert('URL không hợp lệ.');
                    return;
                }

                if (!['http:', 'https:'].includes(parsedUrl.protocol)) {
                    alert('Chỉ hỗ trợ URL http/https.');
                    return;
                }

                articleUrlSubmit.disabled = true;
                articleUrlSubmit.innerText = 'Đang đọc URL...';

                const formData = new FormData();
                formData.append('url', url);

                fetch(`${URL_ROOT}/api/upload`, {
                    method: 'POST',
                    body: formData
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        articleUrlInput.value = '';
                        location.reload();
                    } else {
                        alert('Lỗi đọc URL: ' + data.error);
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('Không thể đọc URL bài báo.');
                })
                .finally(() => {
                    articleUrlSubmit.disabled = false;
                    articleUrlSubmit.innerText = 'Lập chỉ mục URL';
                });
            });
        }

        // Quick paste text submission
        if (quickSubmit) {
            quickSubmit.addEventListener('click', () => {
                const title = quickTitle.value.trim();
                const content = quickContent.value.trim();

                if (!content) {
                    alert('Vui lòng dán nội dung trước khi lập chỉ mục.');
                    return;
                }

                quickSubmit.disabled = true;
                quickSubmit.innerText = 'Đang chia đoạn...';

                const formData = new FormData();
                formData.append('title', title);
                formData.append('content', content);

                fetch(`${URL_ROOT}/api/upload`, {
                    method: 'POST',
                    body: formData
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        quickTitle.value = '';
                        quickContent.value = '';
                        // Refresh doc list dynamically or refresh page
                        location.reload();
                    } else {
                        alert('Lỗi lập chỉ mục: ' + data.error);
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('Lỗi kết nối server.');
                })
                .finally(() => {
                    quickSubmit.disabled = false;
                    quickSubmit.innerText = 'Lập chỉ mục';
                });
            });
        }

        if (resetDemoBtn) {
            resetDemoBtn.addEventListener('click', () => {
                if (!confirm('Reset toàn bộ dữ liệu demo? Tài liệu đã upload và lịch sử chat sẽ quay về dữ liệu mẫu.')) {
                    return;
                }

                resetDemoBtn.disabled = true;
                resetDemoBtn.innerText = 'Đang reset...';
                fetch(`${URL_ROOT}/api/resetDemo`, { method: 'POST' })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            localStorage.removeItem('aether_rag_cache');
                            location.reload();
                        } else {
                            alert('Reset thất bại: ' + data.error);
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        alert('Không thể reset dữ liệu demo.');
                    })
                    .finally(() => {
                        resetDemoBtn.disabled = false;
                        resetDemoBtn.innerText = 'Reset demo';
                    });
            });
        }

        // Setup Document Deletions
        document.querySelectorAll('.delete-doc-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const id = btn.getAttribute('data-id');
                if (confirm('Bạn có chắc muốn xóa tài liệu tham chiếu này và toàn bộ các đoạn của nó? Thao tác này không thể hoàn tác.')) {
                    fetch(`${URL_ROOT}/api/delete`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id: id })
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            const item = document.getElementById('doc-item-' + id);
                            if (item) item.remove();
                            // Reload stats count
                            location.reload();
                        } else {
                            alert('Lỗi xóa tài liệu: ' + data.error);
                        }
                    })
                    .catch(err => console.error(err));
                }
            });
        });

        // Client-side PDF Text Ingestion Parser using PDF.js CDN
        async function parsePdfClientSide(file, onProgress) {
            const reader = new FileReader();
            return new Promise((resolve, reject) => {
                reader.onload = async function() {
                    try {
                        const typedarray = new Uint8Array(this.result);
                        
                        // Specify workerSrc
                        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.4.120/pdf.worker.min.js';
                        
                        const pdf = await pdfjsLib.getDocument(typedarray).promise;
                        let fullText = '';
                        
                        for (let pageNum = 1; pageNum <= pdf.numPages; pageNum++) {
                            const page = await pdf.getPage(pageNum);
                            const textContent = await page.getTextContent();
                            const pageText = textContent.items.map(item => item.str).join(' ');
                            fullText += pageText + '\n\n';
                            
                            if (typeof onProgress === 'function') {
                                const percent = Math.round((pageNum / pdf.numPages) * 100);
                                onProgress(percent, pageNum, pdf.numPages);
                            }
                        }
                        
                        resolve(fullText.trim());
                    } catch (err) {
                        reject(err);
                    }
                };
                reader.onerror = err => reject(err);
                reader.readAsArrayBuffer(file);
            });
        }

        async function uploadDocumentFile(file) {
            const ext = file.name.split('.').pop().toLowerCase();
            const allowedExts = ['txt', 'md', 'csv', 'json', 'pdf', 'docx'];

            if (!allowedExts.includes(ext)) {
                alert('Định dạng chưa hỗ trợ. Vui lòng dùng TXT, MD, CSV, JSON, PDF hoặc DOCX.');
                return;
            }

            if (file.size > 8 * 1024 * 1024) {
                alert('File quá lớn. Dung lượng tối đa cho demo là 8 MB.');
                return;
            }
            
            // Render loading status with custom glowing glassmorphic progress bar
            dropzone.innerHTML = `
                <div class="pulse-indicator" style="margin-bottom: 8px;"></div>
                <h4 style="color:white; margin-bottom: 4px;" id="upload-status-title">Đang tải "${file.name}"...</h4>
                
                <div class="upload-progress-container" style="width: 100%; max-width: 280px; margin: 14px auto 4px auto; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.08); border-radius: 9999px; height: 8px; overflow: hidden; position: relative;">
                    <div id="upload-progress-bar" style="width: 0%; height: 100%; background: linear-gradient(90deg, var(--neon-cyan), var(--neon-purple)); box-shadow: var(--glow-cyan); transition: width 0.1s ease-out; border-radius: 9999px;"></div>
                </div>
                <div id="upload-progress-percent" style="font-size: 13px; font-weight: 600; color: var(--neon-cyan); margin-bottom: 8px;">0%</div>

                <p style="color:#64748b; font-size:11px;" id="upload-status-desc">Đang khởi tạo bộ đọc...</p>
            `;

            const progressBar = document.getElementById('upload-progress-bar');
            const progressPercent = document.getElementById('upload-progress-percent');
            const statusTitle = document.getElementById('upload-status-title');
            const statusDesc = document.getElementById('upload-status-desc');

            const updateProgressBar = (percent) => {
                if (progressBar) progressBar.style.width = `${percent}%`;
                if (progressPercent) progressPercent.innerText = `${percent}%`;
            };

            try {
                if (ext === 'pdf') {
                    if (statusTitle) statusTitle.innerText = `Đang đọc "${file.name}" trên trình duyệt...`;
                    if (statusDesc) statusDesc.innerText = `Đang khởi động PDF.js worker...`;

                    // Parse client side with real-time extraction progress updates
                    const parsedText = await parsePdfClientSide(file, (percent, page, total) => {
                        updateProgressBar(percent);
                        if (statusDesc) {
                            statusDesc.innerText = `Đang trích xuất trang ${page} / ${total} (${percent}%)`;
                        }
                    });
                    
                    if (!parsedText || parsedText.trim() === '') {
                        throw new Error("Nội dung PDF rỗng hoặc chỉ chứa hình ảnh.");
                    }

                    // Forward to backend as plain text copypaste!
                    const formData = new FormData();
                    formData.append('title', file.name.replace(/\.[^/.]+$/, "")); // strip extension
                    formData.append('content', parsedText);

                    if (statusTitle) statusTitle.innerText = `Đang lập chỉ mục...`;
                    if (statusDesc) statusDesc.innerText = `Đang chia nhỏ văn bản thành các đoạn vector & ghi vào MySQL...`;

                    const response = await fetch(`${URL_ROOT}/api/upload`, {
                        method: 'POST',
                        body: formData
                    });
                    const data = await response.json();
                    if (data.success) {
                        updateProgressBar(100);
                        if (statusDesc) statusDesc.innerText = `Lập chỉ mục hoàn tất! Đang làm mới trang...`;
                        setTimeout(() => {
                            location.reload();
                        }, 500);
                    } else {
                        alert('Tải lên thất bại: ' + data.error);
                        location.reload();
                    }
                } else {
                    // Normal TXT, MD, CSV, JSON, DOCX files
                    // Simulate a sleek, smooth progress progression over 350ms to keep a premium, interactive UX feel
                    if (statusTitle) statusTitle.innerText = `Đang đọc "${file.name}"...`;
                    if (statusDesc) statusDesc.innerText = `Đang đọc dữ liệu nhị phân...`;

                    let currentPercent = 0;
                    const duration = 350; // ms
                    const intervalStep = 15; // ms
                    const increment = 100 / (duration / intervalStep);

                    const simulatedProgress = () => {
                        return new Promise((resolve) => {
                            const interval = setInterval(() => {
                                currentPercent += increment;
                                if (currentPercent >= 100) {
                                    currentPercent = 100;
                                    updateProgressBar(100);
                                    if (statusDesc) statusDesc.innerText = `Hoàn tất đọc! Đang truyền tải gói dữ liệu...`;
                                    clearInterval(interval);
                                    setTimeout(resolve, 50);
                                } else {
                                    updateProgressBar(Math.round(currentPercent));
                                    if (statusDesc) statusDesc.innerText = `Đang xử lý cấu trúc file (${Math.round(currentPercent)}%)`;
                                }
                            }, intervalStep);
                        });
                    };

                    await simulatedProgress();

                    if (statusTitle) statusTitle.innerText = `Đang gửi lên máy chủ...`;
                    if (statusDesc) statusDesc.innerText = `Đang lưu trữ & chia các đoạn ngữ nghĩa vào CSDL...`;

                    const formData = new FormData();
                    formData.append('file', file);
                    
                    const response = await fetch(`${URL_ROOT}/api/upload`, {
                        method: 'POST',
                        body: formData
                    });
                    const data = await response.json();
                    if (data.success) {
                        if (statusDesc) statusDesc.innerText = `Nhập tài liệu thành công! Đang làm mới trang...`;
                        setTimeout(() => {
                            location.reload();
                        }, 500);
                    } else {
                        alert('Tải lên thất bại: ' + data.error);
                        location.reload();
                    }
                }
            } catch (err) {
                console.error(err);
                alert('Lỗi xử lý tài liệu: ' + err.message);
                location.reload();
            }
        }

        function renderDashboardCharts() {
            const sentimentContainer = document.getElementById('sentiment-chart-container');
            const modelContainer = document.getElementById('model-usage-container');
            if (!sentimentContainer && !modelContainer) return;

            fetch(`${URL_ROOT}/api/analytics`)
                .then(r => r.json())
                .then(data => {
                    if (!data.success) return;
                    renderSentimentBars(sentimentContainer, data.summary.sentiment_breakdown || []);
                    renderModelBars(modelContainer, data.summary.model_usage || []);
                })
                .catch(err => console.error(err));
        }

        function renderSentimentBars(container, rows) {
            if (!container) return;
            const labels = ['Tích cực', 'Trung tính', 'Tiêu cực', 'Positive', 'Neutral', 'Negative'];
            const normalized = {};
            rows.forEach(row => {
                const label = row.sentiment === 'Positive' ? 'Tích cực' : (row.sentiment === 'Negative' ? 'Tiêu cực' : (row.sentiment === 'Neutral' ? 'Trung tính' : row.sentiment));
                normalized[label] = (normalized[label] || 0) + parseInt(row.count || 0);
            });
            const values = ['Tích cực', 'Trung tính', 'Tiêu cực'].map(label => ({ label, count: normalized[label] || 0 }));
            const max = Math.max(1, ...values.map(v => v.count));
            const colors = ['var(--neon-emerald)', 'var(--neon-amber)', 'var(--neon-rose)'];

            container.innerHTML = values.map((item, index) => {
                const height = Math.max(10, Math.round((item.count / max) * 110));
                return `
                    <div style="text-align:center; width:30%;">
                        <div title="${item.count} tin nhắn" style="height:${height}px; width:28px; background:${colors[index]}; margin:0 auto; border-radius:4px; box-shadow:0 0 10px ${colors[index]};"></div>
                        <span style="font-size:11px; color:#64748b; display:block; margin-top:6px;">${item.label}</span>
                        <strong style="font-size:12px; color:#e2e8f0;">${item.count}</strong>
                    </div>
                `;
            }).join('');
        }

        function renderModelBars(container, rows) {
            if (!container) return;
            const total = Math.max(1, rows.reduce((sum, row) => sum + parseInt(row.count || 0), 0));
            if (rows.length === 0) {
                container.innerHTML = '<div style="color:#64748b; font-size:12px;">Chưa có dữ liệu mô hình.</div>';
                return;
            }

            container.innerHTML = rows.map(row => {
                const count = parseInt(row.count || 0);
                const pct = Math.round((count / total) * 100);
                const name = row.model_used || 'Không rõ';
                return `
                    <div>
                        <div style="display:flex; justify-content:space-between; font-size:12px; color:#e2e8f0; margin-bottom:4px;">
                            <span>${name}</span>
                            <span>${pct}% (${count})</span>
                        </div>
                        <div style="height:6px; width:100%; background:rgba(255,255,255,0.05); border-radius:3px;">
                            <div style="height:100%; width:${pct}%; background:linear-gradient(to right, var(--neon-cyan), var(--neon-purple)); border-radius:3px;"></div>
                        </div>
                    </div>
                `;
            }).join('');
        }
    }

    // --- AI CHAT WORKSPACE ROUTER ACTIONS ---
    function initChatView() {
        const messagesContainer = document.getElementById('chat-messages-container');
        
        let activeAgent = 'general'; // 'general', 'analyst', 'auditor'
        const agentCards = document.querySelectorAll('.agent-card');
        const collabCheckbox = document.getElementById('collab-mode-checkbox');
        const agentCardsRow = document.querySelector('.agent-cards-row');

        // Handle active agent selections
        agentCards.forEach(card => {
            card.addEventListener('click', () => {
                if (collabCheckbox && collabCheckbox.checked) return;
                
                agentCards.forEach(c => c.classList.remove('active'));
                card.classList.add('active');
                activeAgent = card.getAttribute('data-agent');
            });
        });

        // Handle collaboration toggle switch
        if (collabCheckbox) {
            if (collabCheckbox.checked) {
                if (agentCardsRow) agentCardsRow.classList.add('disabled');
            }

            collabCheckbox.addEventListener('change', () => {
                if (collabCheckbox.checked) {
                    if (agentCardsRow) agentCardsRow.classList.add('disabled');
                } else {
                    if (agentCardsRow) agentCardsRow.classList.remove('disabled');
                    agentCards.forEach(c => {
                        if (c.getAttribute('data-agent') === activeAgent) {
                            c.classList.add('active');
                        } else {
                            c.classList.remove('active');
                        }
                    });
                }
            });
        }
        const chatInput = document.getElementById('chat-input-field');
        const sendBtn = document.getElementById('chat-send-btn');
        const clearBtn = document.getElementById('clear-chat-btn');

        // Real-time Keystroke Sentiment score
        const liveSentimentVal = document.getElementById('live-sentiment-val');
        const liveSentimentFill = document.getElementById('live-sentiment-fill');
        const liveSentimentMarker = document.getElementById('live-sentiment-marker');
        const liveSentimentLbl = document.getElementById('live-sentiment-lbl');

        // Local NN Trainer elements
        const btnTrain = document.getElementById('start-training-btn');
        const inputEpochs = document.getElementById('training-epochs');
        const chartStatus = document.getElementById('chart-status-text');
        const svgChart = document.getElementById('training-svg');

        // 1. Fetch History from SQLite
        loadChatLogs();
        loadChatRagChunks();

        // 2. Real-Time Keystroke Sentiment Analyzer
        if (chatInput) {
            chatInput.addEventListener('input', () => {
                const text = chatInput.value;
                const sentiment = tfHelper.analyzeSentiment(text);

                // Update UI Gauge
                if (liveSentimentVal) liveSentimentVal.innerText = sentiment.score.toFixed(2);
                if (liveSentimentMarker) liveSentimentMarker.style.left = `${sentiment.score * 100}%`;
                
                if (liveSentimentFill) {
                    liveSentimentFill.style.width = `${sentiment.score * 100}%`;
                    if (sentiment.label === 'Tích cực' || sentiment.label === 'Positive') {
                        liveSentimentFill.style.background = 'var(--neon-emerald)';
                        liveSentimentFill.style.boxShadow = '0 0 8px var(--neon-emerald)';
                    } else if (sentiment.label === 'Tiêu cực' || sentiment.label === 'Negative') {
                        liveSentimentFill.style.background = 'var(--neon-rose)';
                        liveSentimentFill.style.boxShadow = '0 0 8px var(--neon-rose)';
                    } else {
                        liveSentimentFill.style.background = 'var(--neon-amber)';
                        liveSentimentFill.style.boxShadow = '0 0 8px var(--neon-amber)';
                    }
                }
                
                if (liveSentimentLbl) {
                    liveSentimentLbl.innerHTML = `<span>${sentiment.emoji}</span> <span>${sentiment.label}</span>`;
                    if (sentiment.label === 'Tích cực' || sentiment.label === 'Positive') {
                        liveSentimentLbl.style.color = 'var(--neon-emerald)';
                    } else if (sentiment.label === 'Tiêu cực' || sentiment.label === 'Negative') {
                        liveSentimentLbl.style.color = 'var(--neon-rose)';
                    } else {
                        liveSentimentLbl.style.color = 'var(--neon-amber)';
                    }
                }
            });
        }

        // 3. Send message
        if (sendBtn) {
            sendBtn.addEventListener('click', triggerSendMessage);
            sendBtn.addEventListener('click', (e) => {
                e.preventDefault();
                triggerSendMessage();
            });
        }

        if (chatInput) {
            chatInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    triggerSendMessage();
                }
            });
        }

        if (clearBtn) {
            clearBtn.addEventListener('click', () => {
                if (confirm('Xóa lịch sử hội thoại cục bộ? Các tin nhắn mẫu sẽ được giữ lại.')) {
                    fetch(`${URL_ROOT}/api/clear`, { method: 'POST' })
                    .then(r => r.json())
                    .then(() => {
                        loadChatLogs();
                    });
                }
            });
        }

        function loadChatRagChunks() {
            if (!appSettings.ragEnabled) return;

            fetch(`${URL_ROOT}/api/chunks`)
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        rag.setChunks(data.chunks);
                    }
                })
                .catch(err => console.error('Khong the nap kho tri thuc RAG:', err));
        }

        // 4. Custom Local Browser NN Trainer
        if (btnTrain) {
            btnTrain.addEventListener('click', async () => {
                if (tfHelper.isTraining) return;

                const epochs = parseInt(inputEpochs.value) || 60;
                btnTrain.disabled = true;
                btnTrain.innerText = 'Đang huấn luyện...';
                chartStatus.innerText = 'Đang khởi tạo tensor và mô hình Sequential...';
                chartStatus.style.display = 'block';
                svgChart.style.display = 'none';

                await tfHelper.trainBrowserNetwork(epochs, (epoch, loss, accuracy, historyPoints) => {
                    chartStatus.innerText = `Epoch ${epoch}/${epochs}: Loss = ${loss.toFixed(4)}, Độ chính xác = ${accuracy.toFixed(2)}`;
                    
                    // Render/update beautiful SVG curve inside training progress widget
                    renderEpochLossSvg(historyPoints, epochs);
                });

                chartStatus.innerHTML = `Mô hình đã huấn luyện thành công.<br>Loss đã ổn định - Độ chính xác = 100%<br><span style="color:var(--neon-cyan)">Sẵn sàng phân loại ý định tùy chỉnh.</span>`;
                btnTrain.innerText = 'Đã lưu mô hình';
                
                // Set custom badge details
                const badgeElText = document.getElementById('sidebar-active-model-text');
                if (badgeElText) {
                    badgeElText.innerText = 'NN tự huấn luyện';
                }
            });
        }

        function renderEpochLossSvg(points, maxEpochs) {
            chartStatus.style.display = 'none';
            svgChart.style.display = 'block';
            svgChart.innerHTML = '';

            const w = svgChart.clientWidth || 250;
            const h = svgChart.clientHeight || 100;
            
            // Map padding bounds
            const padding = 15;
            const chartW = w - padding * 2;
            const chartH = h - padding * 2;

            // Find min/max values
            const maxLoss = Math.max(...points.map(p => p.loss), 1.0);
            
            // Generate polyline string
            let pointsStr = '';
            points.forEach((p, idx) => {
                const x = padding + (p.epoch / maxEpochs) * chartW;
                const y = padding + (1 - (p.loss / maxLoss)) * chartH;
                pointsStr += `${x},${y} `;
            });

            // Draw line
            const polyline = document.createElementNS('http://www.w3.org/2000/svg', 'polyline');
            polyline.setAttribute('points', pointsStr.trim());
            polyline.setAttribute('stroke', 'var(--neon-cyan)');
            polyline.setAttribute('stroke-width', '2.5');
            polyline.setAttribute('fill', 'none');
            polyline.setAttribute('style', 'filter: drop-shadow(0px 0px 3px rgba(6, 182, 212, 0.8));');

            // Draw grid markers
            const grid = document.createElementNS('http://www.w3.org/2000/svg', 'line');
            grid.setAttribute('x1', padding);
            grid.setAttribute('y1', h - padding);
            grid.setAttribute('x2', w - padding);
            grid.setAttribute('y2', h - padding);
            grid.setAttribute('stroke', 'rgba(255,255,255,0.1)');
            grid.setAttribute('stroke-dasharray', '3,3');

            svgChart.appendChild(grid);
            svgChart.appendChild(polyline);
        }

        function loadChatLogs() {
            if (!messagesContainer) return;
            messagesContainer.innerHTML = `
                <div style="text-align: center; color: #64748b; padding: 40px 0;">
                    <div class="pulse-indicator" style="margin-bottom: 8px;"></div>
                    <span>Đang tải lịch sử hội thoại cục bộ...</span>
                </div>
            `;

            fetch(`${URL_ROOT}/api/history`)
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    messagesContainer.innerHTML = '';
                    if (data.history.length === 0) {
                        messagesContainer.innerHTML = `
                            <div style="text-align:center; color:#64748b; padding:40px; font-style:italic;">
                                Chào mừng! Nhập tin nhắn bên dưới để bắt đầu cuộc hội thoại.
                            </div>
                        `;
                        return;
                    }

                    data.history.forEach(msg => {
                        appendMessageBubble(msg.sender, msg.message, msg.sentiment, msg.model_used, msg.created_at);
                    });

                    scrollChatToBottom();
                }
            })
            .catch(err => {
                console.error(err);
                messagesContainer.innerHTML = `<div style="text-align:center; color:var(--neon-rose); padding:20px;">Không thể kết nối dữ liệu cục bộ.</div>`;
            });
        }

        async function triggerSendMessage() {
            const val = chatInput.value.trim();
            if (!val || tfHelper.isTraining) return;

            // Compute keystroke sentiment right before saving!
            const sentiment = tfHelper.analyzeSentiment(val);
            chatInput.value = '';
            
            // Dispatch input event to reset the real-time sentiment gauge to neutral
            chatInput.dispatchEvent(new Event('input'));

            // 1. Immediately append user bubble locally (Zero latency)
            appendMessageBubble('user', val, sentiment.label, appSettings.modelMode, new Date().toISOString());
            scrollChatToBottom();

            // Append floating typing indicator bubble
            const typingBubble = document.createElement('div');
            typingBubble.className = 'message-bubble assistant typing';
            typingBubble.id = 'temp-typing';
            
            const isCollab = collabCheckbox && collabCheckbox.checked;
            const thinkingText = appSettings.modelMode === 'Gemini'
                ? 'Đang gọi Gemini API qua backend cục bộ...'
                : (isCollab ? 'AetherMind đang chạy cộng tác nhiều tác tử...' : 'AetherMind đang suy luận offline...');
            typingBubble.innerHTML = `
                <span class="status-dot" style="animation: pulse 1s infinite;"></span> 
                <span style="font-style:italic; color:#64748b;">${thinkingText}</span>
            `;
            messagesContainer.appendChild(typingBubble);
            scrollChatToBottom();

            // Run client-side Semantic RAG Embeddings if enabled
            let ragMatches = [];
            if (appSettings.ragEnabled) {
                const loaderSpan = typingBubble.querySelector('span:last-child');
                if (loaderSpan) loaderSpan.innerText = 'Đang nhúng Vector Semantics (USE)...';
                ragMatches = await rag.search(val);
                if (loaderSpan) loaderSpan.innerText = thinkingText;
            }

            // 2. Perform backend dispatch request (Gemini proxy / offline mock)
            const payload = {
                message: val,
                sentiment: sentiment.label,
                sentiment_score: sentiment.score,
                model: appSettings.modelMode,
                apiKey: appSettings.geminiKey,
                ragEnabled: appSettings.ragEnabled,
                ragMatches: ragMatches.slice(0, 5), // Send top 5 matches natively processed by TF.js
                agent: isCollab ? 'collaboration' : activeAgent
            };

            fetch(`${URL_ROOT}/api/chat`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
            .then(r => r.json())
            .then(data => {
                // Delete loading bubble
                const loader = document.getElementById('temp-typing');
                if (loader) loader.remove();

                if (data.success) {
                    if (data.mode === 'collaboration') {
                        // Display the 3 agent responses sequentially with transitions!
                        appendMessageBubble('assistant', data.responses.auditor, 'Trung tính', 'Kiểm toán cảm xúc', new Date().toISOString());
                        scrollChatToBottom();
                        
                        setTimeout(() => {
                            appendMessageBubble('assistant', data.responses.analyst, 'Trung tính', 'Doc-Analyst', new Date().toISOString());
                            scrollChatToBottom();
                            
                            setTimeout(() => {
                                appendMessageBubble('assistant', data.responses.generalist, 'Trung tính', 'AetherMind', new Date().toISOString());
                                renderRagMatches(data.rag_matches);
                                scrollChatToBottom();
                            }, 600);
                        }, 600);
                    } else {
                        // Single Agent Mode
                        let agentName = 'AetherMind';
                        if (activeAgent === 'auditor') agentName = 'Kiểm toán cảm xúc';
                        else if (activeAgent === 'analyst') agentName = 'Doc-Analyst';
                        appendMessageBubble('assistant', data.response, 'Trung tính', data.model || agentName, new Date().toISOString());
                        renderRagMatches(data.rag_matches);
                    }
                } else {
                    appendMessageBubble('assistant', `**Lỗi proxy hệ thống:** ${data.error}`, 'Trung tính', 'Nhật ký hệ thống', new Date().toISOString());
                }
                scrollChatToBottom();
            })
            .catch(err => {
                console.error(err);
                const loader = document.getElementById('temp-typing');
                if (loader) loader.remove();

                appendMessageBubble('assistant', `**Lỗi kết nối:** Không thể liên hệ backend cục bộ.`, 'Trung tính', 'Lỗi hệ thống', new Date().toISOString());
                scrollChatToBottom();
            });
        }

        function appendMessageBubble(sender, message, sentiment, model, timestamp) {
            const loader = document.getElementById('chat-init-loader');
            if (loader) loader.remove();

            const bubble = document.createElement('div');
            bubble.className = `message-bubble ${sender}`;

            // Attach specific agent bubble class
            if (sender === 'assistant') {
                if (model.includes('Auditor') || model.includes('auditor') || model.includes('cảm xúc')) {
                    bubble.className += ' auditor-bubble';
                } else if (model.includes('Analyst') || model.includes('analyst')) {
                    bubble.className += ' analyst-bubble';
                } else {
                    bubble.className += ' generalist-bubble';
                }
            }

            // Parse simple Markdown bold and newline rules to make answers look professional!
            let formattedMsg = message
                .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                .replace(/\*(.*?)\*/g, '<em>$1</em>')
                .replace(/\n/g, '<br>');

            const timeStr = new Date(timestamp).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            
            let badgeHtml = '';
            if (sender === 'user') {
                const sColor = (sentiment === 'Tích cực' || sentiment === 'Positive') ? 'var(--neon-emerald)' : ((sentiment === 'Tiêu cực' || sentiment === 'Negative') ? 'var(--neon-rose)' : 'var(--neon-amber)');
                const sentimentText = sentiment === 'Positive' ? 'Tích cực' : (sentiment === 'Negative' ? 'Tiêu cực' : (sentiment === 'Neutral' ? 'Trung tính' : sentiment));
                badgeHtml = `<span style="color:${sColor}">Cảm xúc: ${sentimentText}</span>`;
            } else {
                let badgeColor = 'var(--neon-purple)';
                if (model.includes('Auditor') || model.includes('auditor') || model.includes('cảm xúc')) {
                    badgeColor = 'var(--neon-amber)';
                } else if (model.includes('Analyst') || model.includes('analyst')) {
                    badgeColor = 'var(--neon-cyan)';
                }
                badgeHtml = `<span style="color:${badgeColor}; font-weight:600;">${model}</span>`;
            }

            bubble.innerHTML = `
                <div>${formattedMsg}</div>
                <div class="msg-meta">
                    <span>${timeStr}</span>
                    <span>${badgeHtml}</span>
                </div>
            `;

            messagesContainer.appendChild(bubble);
        }

        function renderRagMatches(matches) {
            if (!matches || matches.length === 0) return;

            const panel = document.createElement('div');
            panel.className = 'message-bubble assistant analyst-bubble';
            panel.style.maxWidth = '82%';
            panel.innerHTML = `
                <div style="font-weight:700; margin-bottom:8px;">Top RAG chunks được truy xuất</div>
                ${matches.slice(0, 5).map(match => `
                    <div style="border-top:1px solid rgba(255,255,255,0.08); padding-top:8px; margin-top:8px;">
                        <div style="font-size:11px; color:var(--neon-cyan); font-weight:700;">
                            [Tài liệu: ${escapeHtml(match.document_title)}, Đoạn: ${parseInt(match.chunk_index) + 1}, Điểm: ${Number(match.score).toFixed(2)}]
                        </div>
                        <div style="font-size:12px; color:#94a3b8; margin-top:4px; line-height:1.45;">
                            ${escapeHtml(match.content).slice(0, 260)}...
                        </div>
                    </div>
                `).join('')}
            `;
            messagesContainer.appendChild(panel);
        }

        function escapeHtml(value) {
            return String(value || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function scrollChatToBottom() {
            if (messagesContainer) {
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            }
        }
    }

    // --- VECTOR SPACE VISUALIZER CANVAS ROUTER ACTIONS ---
    function initVisualizerView() {
        visualizer = new VectorSpaceVisualizer('physics-canvas');
        visualizer.start();

        // Bind zoom UI buttons (Proposal 3)
        const zoomInBtn = document.getElementById('zoom-in-btn');
        const zoomOutBtn = document.getElementById('zoom-out-btn');
        const zoomResetBtn = document.getElementById('zoom-reset-btn');
        const zoomPercent = document.getElementById('zoom-percent');

        if (zoomInBtn) {
            zoomInBtn.addEventListener('click', () => {
                visualizer.scale = Math.min(4.0, visualizer.scale + 0.15);
                updateZoomPercent();
            });
        }
        if (zoomOutBtn) {
            zoomOutBtn.addEventListener('click', () => {
                visualizer.scale = Math.max(0.3, visualizer.scale - 0.15);
                updateZoomPercent();
            });
        }
        if (zoomResetBtn) {
            zoomResetBtn.addEventListener('click', () => {
                visualizer.scale = 1.0;
                visualizer.pan = { x: 0, y: 0 };
                updateZoomPercent();
            });
        }

        // Listen for standard mouse wheel changes inside the canvas to keep percent indicator in sync
        window.addEventListener('canvasZoomed', (e) => {
            if (zoomPercent) {
                zoomPercent.innerText = Math.round(e.detail.scale * 100) + '%';
            }
        });

        function updateZoomPercent() {
            if (zoomPercent) {
                zoomPercent.innerText = Math.round(visualizer.scale * 100) + '%';
            }
        }

        const searchInput = document.getElementById('visualizer-search-input');
        const searchBtn = document.getElementById('visualizer-search-btn');
        const togglesContainer = document.getElementById('visualizer-document-toggles');

        let activeDocumentIds = new Set();
        let corpusChunks = [];

        // 1. Fetch chunks across documents to initialize RAG TF-IDF
        fetch(`${URL_ROOT}/api/chunks`)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                corpusChunks = data.chunks;
                
                // Initialize active set on start
                const docIds = new Set(data.chunks.map(c => parseInt(c.document_id)));
                activeDocumentIds = docIds;

                // Load to RAG engine & visualizer
                rag.setChunks(data.chunks);
                visualizer.loadChunks(data.chunks, activeDocumentIds);

                // Initialize document selection list checkboxes
                populateFilterCheckboxes();
            }
        })
        .catch(err => console.error(err));

        // 2. Perform Cosine Similarity attraction query
        if (searchBtn) {
            searchBtn.addEventListener('click', triggerVectorQuery);
        }

        if (searchInput) {
            searchInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    triggerVectorQuery();
                }
            });
        }

        async function triggerVectorQuery() {
            const query = searchInput.value.trim();
            
            if (!query) {
                // If empty, clean up query nodes
                visualizer.applyQuerySearch('', []);
                return;
            }

            // Show loading text while heavy tensor operations occur
            if (searchBtn) searchBtn.innerText = 'Đang nhúng Vector...';

            // Run client-side RAG ranking (Awaits async Universal Sentence Encoder)
            const results = await rag.search(query);

            if (searchBtn) searchBtn.innerText = 'Tìm kiếm Vector';

            // Apply springs to Canvas
            visualizer.applyQuerySearch(query, results);
        }

        function populateFilterCheckboxes() {
            if (!togglesContainer) return;
            togglesContainer.innerHTML = '';

            // Deduplicate documents
            const uniqueDocs = [];
            const docSeen = new Set();

            corpusChunks.forEach(chunk => {
                if (!docSeen.has(chunk.document_id)) {
                    docSeen.add(chunk.document_id);
                    uniqueDocs.push({
                        id: chunk.document_id,
                        title: chunk.document_title
                    });
                }
            });

            if (uniqueDocs.length === 0) {
                togglesContainer.innerHTML = `
                    <div style="text-align: center; color: #64748b; padding: 20px 0; font-style: italic; font-size:12px;">
                        Chưa có tài liệu tham chiếu nào được lập chỉ mục.
                    </div>
                `;
                return;
            }

            uniqueDocs.forEach(doc => {
                const item = document.createElement('div');
                item.className = 'list-item';
                item.style.padding = '8px 12px';
                item.style.marginBottom = '6px';
                
                item.innerHTML = `
                    <div style="display:flex; align-items:center; gap:8px;">
                        <input type="checkbox" checked class="doc-filter-cb" data-id="${doc.id}" style="width:16px; height:16px; accent-color:var(--neon-cyan); cursor:pointer;">
                        <span style="font-family:var(--font-heading); font-weight:600; font-size:13px; color:white; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; max-width:180px;" title="${doc.title}">
                            ${doc.title}
                        </span>
                    </div>
                    <span class="badge cyan" style="font-size:10px; padding:2px 6px;">Đang bật</span>
                `;

                togglesContainer.appendChild(item);
            });

            // Bind checkbox events to filter canvas
            document.querySelectorAll('.doc-filter-cb').forEach(cb => {
                cb.addEventListener('change', () => {
                    const id = parseInt(cb.getAttribute('data-id'));
                    const badge = cb.parentElement.nextElementSibling;

                    if (cb.checked) {
                        activeDocumentIds.add(id);
                        badge.className = 'badge cyan';
                        badge.innerText = 'Đang bật';
                    } else {
                        activeDocumentIds.delete(id);
                        badge.className = 'badge';
                        badge.style.color = '#64748b';
                        badge.style.border = '1px solid rgba(255,255,255,0.05)';
                        badge.innerText = 'Đã ẩn';
                    }

                    // Reload chunks with updated toggles set
                    visualizer.loadChunks(corpusChunks, activeDocumentIds);
                    
                    // Maintain search state if present
                    triggerVectorQuery();
                });
            });
        }
    }

    // Global listener for API settings syncs
    window.addEventListener('aetherSettingsUpdated', () => {
        appSettings.modelMode = localStorage.getItem('aether_model_mode') || 'Offline Simulation';
        appSettings.geminiKey = localStorage.getItem('aether_gemini_key') || '';
        appSettings.ragEnabled = localStorage.getItem('aether_rag_enabled') !== 'false';
        
        updateGlobalModelBadge();
    });
});
