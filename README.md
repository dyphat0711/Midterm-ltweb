# AetherMind - PHP MVC Intelligent Knowledge Workspace

**AetherMind** is an advanced, responsive, and highly interactive intelligent web application. Built using a lightweight custom PHP MVC architecture, it integrates client-side machine learning, semantic RAG document ingestion, interactive HTML5 Canvas network visualization, and resilient dual-mode database failover.

The project is designed to be fully portable, premium in aesthetics, and extremely simple to run locally.

---

## 🌟 Premium Features & Enhancements

### 1. Robust Dual-Mode Database Engine
* **MySQL on XAMPP Default:** Fully migrated to native MySQL PDO storage to support scalable production-ready indexing.
* **Automatic Graceful Failover:** If the XAMPP MySQL server is offline or unreachable, the system automatically detects the connection failure and switches to a local **Offline JSON Sandboxed Datastore** (`app/database/database.json`). The application never crashes and displays a sleek, amber/rose glassmorphic warning banner informing the developer of the database status.
* **Auto-Schema Ingestion:** On the first successful MySQL connection, the database class automatically creates the `aethermind` database and seeds all schema tables (`documents`, `document_chunks`, `chat_history`).

### 2. Client-Side Semantic RAG (Retrieval-Augmented Generation)
* **Local Ingestion & Chunking:** Ingests document formats including TXT, MD, CSV, JSON, DOCX, and browser-parsed PDF files.
* **Embedding Search:** Operates a browser-side Semantic Search using the **TensorFlow.js Universal Sentence Encoder (USE)** to generate 512-dimension vector embeddings. Matches search queries using client-side **Cosine Similarity** with an automatic keyword-matching fallback if the client is offline.
* **RAG Prompt Synthesis:** Merges retrieved document contexts into LLM prompts, returning synthesized answers with professional source citations (e.g. `[Document: ..., Chunk: ...]`).
* **Session Cache:** Keeps a localized prompt cache to optimize latency on identical repeated questions.

### 3. Dynamic Vector Space Visualizer with Zoom & Pan
* **Interactive Canvas Graph:** Renders document chunks as interconnected nodes on a custom HTML5 Canvas.
* **Smooth Mouse Panning & Zooming:** Drag empty canvas space with the left-mouse button to pan across large networks, or scroll the wheel to zoom centered on your mouse cursor.
* **Dynamic Scaling Visuals:** Dynamically scales fonts, node outlines, and connection line strokes during zooms to avoid clipping, blurring, or excessive thinning.
* **Responsive Control Panel Overlay:** Includes programmatic Zoom In (+), Zoom Out (-), Reset, and a Zoom Ratio indicator (e.g., `100%`) for maximum accessibility.

### 4. Glowing Document Upload Progress Bar
* **Premium Glassmorphic UI:** Ingests files using an animated dropzone containing a gorgeous glowing gradient bar (`--neon-cyan` to `--neon-purple`) with subtle micro-animations.
* **Real-Time PDF Ingestion Tracker:** Directly hooks into the `PDF.js` worker processing loop to show exact page-by-page progress percentages (e.g., *Extracting Page 3 / 10 (30%)*).
* **Simulated Local File Loader:** For fast extensions (TXT, MD, CSV, JSON) that process instantly, a simulated loading progression scales smoothly from 0% to 100% over 350ms, ensuring a high-end, responsive, and unified user experience.

### 5. Multi-Agent Reasoning Pipeline
* **Tác tử Kiểm toán Cảm xúc (Sentiment Auditor):** Analyzes message inputs in real-time using local lexicons and TensorFlow.js, offering empathetic, psychological, and personalized conversational transitions.
* **Tác tử Doc-Analyst (Document Specialist):** Rigidly inspects RAG document contexts, ensuring responses are purely fact-based and attributed to source documents.
* **AetherMind (Generalist Coordinator):** Synthesizes structural details from the Sentiment Auditor and Doc-Analyst into a single coherent, professional, and well-rounded final answer.

---

## 🛠 Technology Stack

* **Backend:** Custom lightweight PHP 8+ MVC Architecture
* **Frontend:** Standard Semantic HTML5, CSS3 Custom Variables (Vanilla Dark Mode System), and Vanilla JavaScript
* **Database:** Dual-mode MySQL on XAMPP (PDO) with automatic Offline JSON fallback
* **Machine Learning & NLP:** 
  - TensorFlow.js CDN (Client-side sentiment analysis and USE sentence embedding)
  - PDF.js CDN (Client-side asynchronous text extraction)
* **Visualization:** High-performance HTML5 Canvas with force-directed physics simulation

---

## 📂 Project Structure

```text
project/
|-- app/
|   |-- config/          # Configurations (Database, Gemini model settings)
|   |-- controllers/     # MVC Controllers (API routes, Page handlers)
|   |-- core/            # App Core classes (Database driver, Router, Controller base)
|   |-- database/        # Offline JSON storage fallback directory
|   |-- models/          # Data Models (ChatMessage, Document, Chunk)
|   `-- views/           # UI templates and layout views
|-- public/
|   |-- css/             # Custom stylesheet design systems
|   |-- js/              # Application scripts (app.js, tfjs-helper.js, rag-engine.js, visualizer.js)
|   `-- index.php        # Unified entrypoint & URL rewrite dispatcher
|-- README.md            # Project developer guide
`-- task.md              # Feature checklist status
```

---

## ⚙️ System Requirements

* **PHP:** Version 8.0 or newer
* **Web Server:** Built-in PHP server or XAMPP Apache
* **Database (Optional but recommended):** XAMPP Control Panel with MySQL enabled on port `3306` (standard `root` user, empty password)
* **Browser:** A modern web browser (Chrome, Edge, Firefox, or Safari)
* **Internet Connection:** Required for loading client CDNs (TensorFlow.js, PDF.js, and Google Fonts)

---

## 🚀 Quick Start Guide

### 1. Run Database Services (MySQL on XAMPP)
1. Open the **XAMPP Control Panel** on your computer.
2. Click **Start** for **Apache** and **MySQL**.
3. Confirm that both services are running and MySQL is active on port `3306`.

### 2. Start the PHP Application Server
1. Open a terminal or PowerShell inside the project root folder:
   ```powershell
   cd "d:\Study\HK2_2526\LTWeb-App\Midterm\project"
   ```
2. Start the built-in PHP development server targeting the `public` folder:
   ```powershell
   php -S localhost:8000 -t public
   ```

### 3. Open in Browser
Open your browser and navigate to:
* **Dashboard / Main Page:** [http://localhost:8000](http://localhost:8000)
* **AI Chat Workspace:** [http://localhost:8000/chat](http://localhost:8000/chat)
* **Vector Space Network:** [http://localhost:8000/visualizer](http://localhost:8000/visualizer)

*Note: If you have modified CSS or JavaScript files, press **`Ctrl + F5`** to completely refresh the browser cache and load the latest updates.*

---

## ⚙️ Advanced Settings & Configurations

### Optional Gemini Cloud Mode
By default, the chatbot runs in **Offline Simulation Mode**. To activate live Gemini responses:
1. Start AetherMind in your browser.
2. Open the **API Settings** tab on the left sidebar.
3. Toggle the active mode to **Gemini**.
4. Input a valid **Gemini API Key**.
5. Save the configuration. 
*(API keys are stored safely on the local browser session and proxied strictly through the local PHP cURL backend for security).*

---

## 🔌 Core API Endpoints

AetherMind runs a modular API routing structure:

| Method | Endpoint | Description |
| :--- | :--- | :--- |
| `GET` | `/api/history` | Fetches local chat logs from the database |
| `POST` | `/api/chat` | Receives queries, runs RAG matching, and generates responses |
| `POST` | `/api/clear` | Clears local chat history |
| `GET` | `/api/analytics` | Generates sentiment averages, totals, and model usage numbers |
| `GET` | `/api/documents` | Lists all indexed RAG documents |
| `GET` | `/api/chunks` | Lists all parsed document chunks and vectors |
| `POST` | `/api/upload` | Uploads and indexes TXT, MD, CSV, JSON, DOCX, or PDF files |
| `POST` | `/api/delete` | Deletes a document and its parsed chunks |
| `POST` | `/api/resetDemo` | Resets all demo tables to clear the presentation state |

---

## 🧪 Code Validation & Verification

Ensure all files are free of syntax errors:

### PHP Syntax Check
Run this inside PowerShell to check all PHP code blocks:
```powershell
Get-ChildItem -Recurse -Filter *.php | ForEach-Object { php -l $_.FullName }
```

### JavaScript Syntax Check
Check all JavaScript code blocks if you have Node.js installed:
```powershell
Get-ChildItem public\js -Filter *.js | ForEach-Object { node --check $_.FullName }
```
