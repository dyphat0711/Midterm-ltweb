/**
 * AetherMind HTML5 Canvas Force-Directed Physics Graph Visualizer
 */

class VectorSpaceVisualizer {
    constructor(canvasId) {
        this.canvas = document.getElementById(canvasId);
        if (!this.canvas) return;

        this.ctx = this.canvas.getContext('2d');
        this.nodes = [];
        this.links = [];
        this.draggedNode = null;
        this.hoveredNode = null;
        this.queryNode = null;
        this.animationId = null;

        // Physics constants
        this.repulsionConstant = 350; // Coulomb constant
        this.damping = 0.88;          // Friction
        this.gravity = 0.03;          // Central gravity pull
        this.springLength = 150;      // Hooke resting length
        this.mousePos = { x: 0, y: 0 };

        // Colors mapping palette (Neon cyans, purples, emeralds, ambers)
        this.colorPalette = ['#00f0ff', '#a855f7', '#10b981', '#f59e0b', '#ec4899', '#3b82f6'];

        // Zoom and Pan state
        this.scale = 1.0;
        this.pan = { x: 0, y: 0 };
        this.isPanning = false;
        this.panStart = { x: 0, y: 0 };

        this.initEvents();
    }

    /**
     * Start the canvas rendering loop
     */
    start() {
        if (!this.canvas) return;
        this.resize();
        if (this.animationId) {
            cancelAnimationFrame(this.animationId);
        }
        
        const tick = () => {
            this.updatePhysics();
            this.draw();
            this.animationId = requestAnimationFrame(tick);
        };
        tick();
    }

    /**
     * Stop loops
     */
    stop() {
        if (!this.canvas) return;
        if (this.animationId) {
            cancelAnimationFrame(this.animationId);
        }
    }

    /**
     * Resize canvas size
     */
    resize() {
        if (!this.canvas) return;
        const parent = this.canvas.parentElement;
        this.canvas.width = parent.clientWidth;
        this.canvas.height = parent.clientHeight;
        
        // Relocate nodes towards center if they are out of bounds after resize
        this.nodes.forEach(node => {
            if (node.x < 0 || node.x > this.canvas.width) node.x = Math.random() * this.canvas.width;
            if (node.y < 0 || node.y > this.canvas.height) node.y = Math.random() * this.canvas.height;
        });
    }

    /**
     * Map document chunks into floating nodes on the coordinate map
     */
    loadChunks(chunks, activeDocIds = new Set()) {
        if (!this.canvas) return;
        this.nodes = [];
        this.links = [];
        this.queryNode = null;

        const w = this.canvas.width || 800;
        const h = this.canvas.height || 500;

        // Group chunk nodes by document to alternate color schemes
        const docColorMap = {};
        let colorIdx = 0;

        chunks.forEach(chunk => {
            // Check if document toggle filters out this chunk
            if (activeDocIds.size > 0 && !activeDocIds.has(parseInt(chunk.document_id))) {
                return;
            }

            // Assign color for this document
            if (!docColorMap.hasOwnProperty(chunk.document_id)) {
                docColorMap[chunk.document_id] = this.colorPalette[colorIdx % this.colorPalette.length];
                colorIdx++;
            }

            // Spawn node at a random circle offset from center
            const angle = Math.random() * Math.PI * 2;
            const radius = 80 + Math.random() * 120;

            this.nodes.push({
                id: chunk.id,
                chunkIndex: chunk.chunk_index,
                documentId: chunk.document_id,
                documentTitle: chunk.document_title,
                content: chunk.content,
                
                // Physics params
                x: (w / 2) + Math.cos(angle) * radius,
                y: (h / 2) + Math.sin(angle) * radius,
                vx: 0,
                vy: 0,
                mass: 1.0,
                radius: 8 + Math.min(6, Math.floor(chunk.word_count / 30)), // radius proportional to word size!
                color: docColorMap[chunk.document_id],
                group: chunk.document_id,
                isDragging: false
            });
        });

        this.updateStats();
    }

    /**
     * Apply a search query term, spawning a central Query node and dynamic springs
     */
    applyQuerySearch(queryText, searchResults) {
        if (!this.canvas) return;
        // 1. Remove previous query node
        this.nodes = this.nodes.filter(n => !n.isQuery);
        this.links = [];
        this.queryNode = null;

        if (!queryText || queryText.trim() === '' || searchResults.length === 0) {
            this.updateStats();
            return;
        }

        const w = this.canvas.width || 800;
        const h = this.canvas.height || 500;

        // 2. Create White Query Node
        this.queryNode = {
            id: 'query-node-' + Date.now(),
            isQuery: true,
            content: `Vector truy vấn: "${queryText}"`,
            documentTitle: 'Vector tìm kiếm đang hoạt động',
            x: w / 2,
            y: h / 2,
            vx: 0,
            vy: 0,
            mass: 2.0, // heavier
            radius: 12,
            color: '#ffffff',
            glowColor: '#ffffff',
            isDragging: false
        };

        this.nodes.push(this.queryNode);

        // Map results for quick score access
        const scoreLookup = {};
        searchResults.forEach(res => {
            scoreLookup[res.chunk_id] = res.score;
        });

        // 3. Connect matching chunk nodes to Query node with active springs
        this.nodes.forEach(node => {
            if (node.isQuery) return;

            if (scoreLookup.hasOwnProperty(node.id)) {
                const score = scoreLookup[node.id];
                
                // Spring link stiffness proportional to TF-IDF Cosine Similarity score!
                this.links.push({
                    source: this.queryNode,
                    target: node,
                    stiffness: 0.05 * score, // Stronger matching results pull harder!
                    score: score
                });
            }
        });

        this.updateStats();
    }

    /**
     * Run Euler physics updates
     */
    updatePhysics() {
        if (!this.canvas) return;
        const w = this.canvas.width;
        const h = this.canvas.height;
        const len = this.nodes.length;

        // 1. Coulomb Repulsion: Every node repels every other node
        for (let i = 0; i < len; i++) {
            const nodeA = this.nodes[i];
            
            for (let j = i + 1; j < len; j++) {
                const nodeB = this.nodes[j];

                const dx = nodeB.x - nodeA.x;
                const dy = nodeB.y - nodeA.y;
                const dist = Math.sqrt(dx * dx + dy * dy) || 1.0;

                // Repel only within a specific bubble to keep network organized
                if (dist < 320) {
                    // Coulomb force equation: force = k * (ma * mb) / r^2
                    const force = this.repulsionConstant / (dist * dist);
                    const fx = (dx / dist) * force;
                    const fy = (dy / dist) * force;

                    // Apply equal and opposite reaction
                    if (!nodeA.isDragging && !nodeA.isQuery) {
                        nodeA.vx -= fx / nodeA.mass;
                        nodeA.vy -= fy / nodeA.mass;
                    }
                    if (!nodeB.isDragging && !nodeB.isQuery) {
                        nodeB.vx += fx / nodeB.mass;
                        nodeB.vy += fy / nodeB.mass;
                    }
                }
            }

            // 2. Central Gravity: Pull floating nodes gently to center coordinates
            if (!nodeA.isDragging && !nodeA.isQuery) {
                const dx = (w / 2) - nodeA.x;
                const dy = (h / 2) - nodeA.y;
                nodeA.vx += dx * this.gravity;
                nodeA.vy += dy * this.gravity;
            }
        }

        // 3. Hooke's Spring Law: Spring connections pull connected nodes
        this.links.forEach(link => {
            const s = link.source;
            const t = link.target;

            const dx = t.x - s.x;
            const dy = t.y - s.y;
            const dist = Math.sqrt(dx * dx + dy * dy) || 1.0;

            // Displacement from resting length
            const displacement = dist - this.springLength;
            
            // Spring force: F = -k * displacement
            const force = link.stiffness * displacement;
            const fx = (dx / dist) * force;
            const fy = (dy / dist) * force;

            // Pull Target towards Source
            if (!t.isDragging) {
                t.vx -= fx / t.mass;
                t.vy -= fy / t.mass;
            }
            // Pull Source towards Target (unless it's the fixed query node)
            if (!s.isDragging && !s.isQuery) {
                s.vx += fx / s.mass;
                s.vy += fy / s.mass;
            }
        });

        // 4. Integrate Velocities, Damping, and Bounds limit
        this.nodes.forEach(node => {
            if (node.isDragging) return;

            // Apply friction damping
            node.vx *= this.damping;
            node.vy *= this.damping;

            // Move position
            node.x += node.vx;
            node.y += node.vy;

            // Boundary collisions
            if (node.x < node.radius) {
                node.x = node.radius;
                node.vx *= -0.5;
            }
            if (node.x > w - node.radius) {
                node.x = w - node.radius;
                node.vx *= -0.5;
            }
            if (node.y < node.radius) {
                node.y = node.radius;
                node.vy *= -0.5;
            }
            if (node.y > h - node.radius) {
                node.y = h - node.radius;
                node.vy *= -0.5;
            }
        });
    }

    /**
     * Render the physics scene on the canvas
     */
    draw() {
        if (!this.canvas) return;
        const w = this.canvas.width;
        const h = this.canvas.height;
        this.ctx.clearRect(0, 0, w, h);

        this.ctx.save();
        // Apply panning and zooming translations
        this.ctx.translate(this.pan.x, this.pan.y);
        this.ctx.scale(this.scale, this.scale);

        // 1. Draw glowing background grid lines
        this.ctx.strokeStyle = 'rgba(255, 255, 255, 0.012)';
        this.ctx.lineWidth = 1 / this.scale;
        const gridSpacing = 40;

        // Render grid lines far beyond bounds to account for panning
        const startX = -Math.max(2000, Math.abs(this.pan.x)) / this.scale;
        const endX = (w + Math.max(2000, Math.abs(this.pan.x))) / this.scale;
        const startY = -Math.max(2000, Math.abs(this.pan.y)) / this.scale;
        const endY = (h + Math.max(2000, Math.abs(this.pan.y))) / this.scale;

        for (let x = Math.floor(startX / gridSpacing) * gridSpacing; x < endX; x += gridSpacing) {
            this.ctx.beginPath();
            this.ctx.moveTo(x, startY);
            this.ctx.lineTo(x, endY);
            this.ctx.stroke();
        }
        for (let y = Math.floor(startY / gridSpacing) * gridSpacing; y < endY; y += gridSpacing) {
            this.ctx.beginPath();
            this.ctx.moveTo(startX, y);
            this.ctx.lineTo(endX, y);
            this.ctx.stroke();
        }

        // 2. Draw connections (links / springs)
        this.links.forEach(link => {
            const s = link.source;
            const t = link.target;

            // Draw line glowing based on cosine similarity score
            this.ctx.beginPath();
            this.ctx.moveTo(s.x, s.y);
            this.ctx.lineTo(t.x, t.y);
            
            // Neon cyan glowing line proportional to score
            this.ctx.strokeStyle = `rgba(6, 182, 212, ${0.1 + link.score * 0.75})`;
            this.ctx.lineWidth = (1 + link.score * 3) / this.scale; // keep line readable on zoom
            
            this.ctx.shadowBlur = 5 * link.score;
            this.ctx.shadowColor = 'var(--neon-cyan)';
            this.ctx.stroke();
        });
        
        // Reset shadows for node boundaries
        this.ctx.shadowBlur = 0;

        // 3. Draw nodes
        this.nodes.forEach(node => {
            this.ctx.beginPath();
            this.ctx.arc(node.x, node.y, node.radius, 0, Math.PI * 2);
            
            // If hovered, draw an elegant halo ring
            if (node === this.hoveredNode) {
                this.ctx.save();
                this.ctx.beginPath();
                this.ctx.arc(node.x, node.y, node.radius + 6 / this.scale, 0, Math.PI * 2);
                this.ctx.strokeStyle = node.color;
                this.ctx.lineWidth = 1.5 / this.scale;
                this.ctx.shadowBlur = 10;
                this.ctx.shadowColor = node.color;
                this.ctx.stroke();
                this.ctx.restore();
            }

            // Fill circle
            this.ctx.fillStyle = node.color;
            this.ctx.save();
            this.ctx.shadowBlur = node.isQuery ? 15 : 8;
            this.ctx.shadowColor = node.color;
            this.ctx.fill();
            this.ctx.restore();

            // Inner circle highlight core
            this.ctx.beginPath();
            this.ctx.arc(node.x, node.y, node.radius * 0.4, 0, Math.PI * 2);
            this.ctx.fillStyle = 'rgba(255, 255, 255, 0.6)';
            this.ctx.fill();

            // Render indices inside nodes for identification
            if (node.radius > 9 && !node.isQuery) {
                this.ctx.font = `bold ${Math.max(6, Math.floor(9 / this.scale))}px var(--font-body)`;
                this.ctx.fillStyle = '#030712';
                this.ctx.textAlign = 'center';
                this.ctx.textBaseline = 'middle';
                this.ctx.fillText(node.chunkIndex + 1, node.x, node.y);
            }
        });

        this.ctx.restore();
    }

    /**
     * Mouse listeners for drags, hovers, zooming, and panning
     */
    initEvents() {
        if (!this.canvas) return;
        // Set coordinates with scale/pan translations (world coords)
        const setMousePos = (e) => {
            const rect = this.canvas.getBoundingClientRect();
            const screenX = e.clientX - rect.left;
            const screenY = e.clientY - rect.top;
            
            this.mousePos.x = (screenX - this.pan.x) / this.scale;
            this.mousePos.y = (screenY - this.pan.y) / this.scale;
        };

        this.canvas.addEventListener('mousemove', (e) => {
            // Panning action takes priority
            if (this.isPanning) {
                this.pan.x = e.clientX - this.panStart.x;
                this.pan.y = e.clientY - this.panStart.y;
                return;
            }

            setMousePos(e);
            
            // Drag action
            if (this.draggedNode) {
                this.draggedNode.x = this.mousePos.x;
                this.draggedNode.y = this.mousePos.y;
                return;
            }

            // Hover checks
            let foundHover = null;
            for (let i = this.nodes.length - 1; i >= 0; i--) {
                const node = this.nodes[i];
                const dx = this.mousePos.x - node.x;
                const dy = this.mousePos.y - node.y;
                const dist = Math.sqrt(dx * dx + dy * dy);

                if (dist < node.radius + 4) {
                    foundHover = node;
                    break;
                }
            }

            if (foundHover !== this.hoveredNode) {
                this.hoveredNode = foundHover;
                if (foundHover) {
                    this.onNodeHover(foundHover);
                } else {
                    this.onNodeUnhover();
                }
            }
        });

        this.canvas.addEventListener('mousedown', (e) => {
            setMousePos(e);
            
            // Check if clicked inside a node
            let clickedNode = null;
            for (let i = this.nodes.length - 1; i >= 0; i--) {
                const node = this.nodes[i];
                const dx = this.mousePos.x - node.x;
                const dy = this.mousePos.y - node.y;
                const dist = Math.sqrt(dx * dx + dy * dy);

                if (dist < node.radius + 4) {
                    clickedNode = node;
                    break;
                }
            }

            if (clickedNode) {
                this.draggedNode = clickedNode;
                clickedNode.isDragging = true;
                clickedNode.vx = 0;
                clickedNode.vy = 0;
            } else {
                // Start panning
                this.isPanning = true;
                this.panStart.x = e.clientX - this.pan.x;
                this.panStart.y = e.clientY - this.pan.y;
            }
        });

        this.canvas.addEventListener('mouseup', () => {
            if (this.draggedNode) {
                this.draggedNode.isDragging = false;
                this.draggedNode = null;
            }
            this.isPanning = false;
        });

        this.canvas.addEventListener('mouseleave', () => {
            if (this.draggedNode) {
                this.draggedNode.isDragging = false;
                this.draggedNode = null;
            }
            this.isPanning = false;
            this.hoveredNode = null;
            this.onNodeUnhover();
        });

        // Mouse Wheel Zoom
        this.canvas.addEventListener('wheel', (e) => {
            e.preventDefault();
            const rect = this.canvas.getBoundingClientRect();
            const mouseX = e.clientX - rect.left;
            const mouseY = e.clientY - rect.top;

            const zoomIntensity = 0.05;
            const wheel = e.deltaY < 0 ? 1 : -1;
            const zoomFactor = Math.exp(wheel * zoomIntensity);

            const worldX = (mouseX - this.pan.x) / this.scale;
            const worldY = (mouseY - this.pan.y) / this.scale;

            this.scale = Math.min(Math.max(0.3, this.scale * zoomFactor), 4.0);

            this.pan.x = mouseX - worldX * this.scale;
            this.pan.y = mouseY - worldY * this.scale;
            
            // Dispatch dynamic zoom event to notify UI
            const event = new CustomEvent('canvasZoomed', { detail: { scale: this.scale } });
            window.dispatchEvent(event);
        });

        window.addEventListener('resize', () => this.resize());
    }

    /**
     * UI trigger when node is hovered
     */
    onNodeHover(node) {
        if (!this.canvas) return;
        const inspector = document.getElementById('visualizer-node-inspector');
        if (!inspector) return;

        if (node.isQuery) {
            inspector.innerHTML = `
                <div style="font-weight: 700; color: white; margin-bottom: 4px;">Node truy vấn</div>
                <div style="font-size: 11px; line-height: 1.4; color: var(--neon-cyan);">${node.content}</div>
            `;
            return;
        }

        const scoreText = node.vx === 0 ? '' : `<div style="color:var(--neon-cyan); font-weight:600; margin-bottom:4px;">Điểm vector: node đang hoạt động</div>`;

        inspector.innerHTML = `
            <div style="font-weight: 700; color: white; margin-bottom: 2px;">Tài liệu: ${node.documentTitle}</div>
            <div style="color: #64748b; font-size: 11px; margin-bottom: 6px;">Thứ tự đoạn: ${node.chunkIndex + 1}</div>
            <div style="font-size: 11px; line-height: 1.4; max-height: 100px; overflow-y: auto; background: rgba(0,0,0,0.2); padding: 8px; border-radius: 6px;">
                "${node.content}"
            </div>
        `;
    }

    onNodeUnhover() {
        if (!this.canvas) return;
        const inspector = document.getElementById('visualizer-node-inspector');
        if (inspector) {
            inspector.innerHTML = `
                <div style="text-align: center; color: #64748b; padding: 20px 0; font-style: italic;">
                    Di chuột lên một node trên Canvas để xem nội dung đoạn văn bản.
                </div>
            `;
        }
    }

    updateStats() {
        if (!this.canvas) return;
        const statsEl = document.getElementById('visualizer-stats-nodecount');
        if (statsEl) {
            statsEl.innerText = `Node: ${this.nodes.length} | Lò xo: ${this.links.length}`;
        }
    }
}
