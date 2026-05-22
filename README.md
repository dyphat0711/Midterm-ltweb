# AetherMind - PHP MVC Intelligent Knowledge Workspace

> Vietnamese summary: AetherMind là đồ án giữa kỳ cho chủ đề Intelligent Web Apps. Ứng dụng dùng PHP MVC, TensorFlow.js, RAG keyword/chunk retrieval, Gemini API tùy chọn và HTML5 Canvas để trực quan hóa tài liệu.

AetherMind is a midterm project for **Web Programming & Applications - 503073**. It demonstrates an intelligent web application built with a lightweight PHP MVC architecture, browser-side ML, local document indexing, simulated multi-agent reasoning, optional Gemini API integration, and a canvas-based vector space visualizer.

The project is intentionally easy to run on a local machine. It does not require Node.js, Vite, Composer, MySQL, or a separate database server.

## Key Features

- Dashboard with conversation, sentiment, document, word-count, and model-usage analytics.
- Real dashboard charts rendered from `/api/analytics`.
- AI Workspace with local chat history, simulated multi-agent responses, optional Gemini mode, and improved loading/error states.
- Browser-side sentiment and neural-network training demo using TensorFlow.js.
- Document indexing for TXT, MD, CSV, JSON, DOCX, browser-parsed PDF uploads, and public article URLs.
- Local RAG retrieval with top-k chunks, score, source document, and citation format: `[Document: ..., Chunk: ...]`.
- Session-level RAG result cache for repeated queries.
- Vector Space page with HTML5 Canvas force visualization for indexed document chunks.
- Demo database reset endpoint for presentations.
- Portable JSON datastore at `app/database/database.json`.

## Technology Stack

- Backend: PHP 8+ custom MVC
- Frontend: HTML, CSS, vanilla JavaScript
- Local data: JSON datastore with a PDO-like wrapper
- Visualization: HTML5 Canvas
- Client ML demo: TensorFlow.js CDN
- Optional cloud model: Google Gemini API through PHP cURL
- Retrieval: keyword/token scoring over chunked local documents, with top-k ranking and citations

## Project Structure

```text
Midterm/
|-- app/
|   |-- config/
|   |-- controllers/
|   |-- core/
|   |-- database/
|   |-- models/
|   `-- views/
|-- public/
|   |-- index.php
|   |-- css/
|   `-- js/
|-- ARCHITECTURE.md
`-- README.md
```

## Requirements

- PHP 8.0 or newer
- A modern browser
- Internet connection for CDN libraries:
  - TensorFlow.js
  - PDF.js
  - Google Fonts

Check PHP:

```powershell
php -v
```

## How To Run

Open PowerShell or a terminal in the project root:

```powershell
cd "D:\Study\HK2_2526\LTWeb-App\Midterm"
php -S localhost:8000 -t public
```

Open:

```text
http://localhost:8000
```

Main routes:

```text
http://localhost:8000/
http://localhost:8000/chat
http://localhost:8000/visualizer
```

If port `8000` is already used:

```powershell
php -S localhost:8080 -t public
```

## Optional Gemini Setup

The application works in Offline Simulation Mode by default.

To use Gemini:

1. Start the app.
2. Open `API Settings` in the left sidebar.
3. Choose Gemini mode.
4. Enter a valid Gemini API key.
5. Save the configuration.

The key is sent only to the local PHP backend endpoint that proxies the Gemini request.

## API Endpoints

```text
GET  /api/history
POST /api/chat
POST /api/clear
GET  /api/analytics
GET  /api/documents
GET  /api/chunks
POST /api/upload
POST /api/delete
POST /api/resetDemo
```

`POST /api/upload` accepts three ingestion modes:

- `file`: TXT, MD, CSV, JSON, DOCX, or browser-extracted PDF text.
- `content` plus optional `title`: manual pasted text.
- `url`: public HTML article URL. The backend extracts title/body text, stores the source URL in the document content, then chunks it for RAG.

Example chat request:

```powershell
Invoke-RestMethod `
  -Uri "http://localhost:8000/api/chat" `
  -Method POST `
  -ContentType "application/json" `
  -Body '{"message":"summarize artificial intelligence","sentiment":"Neutral","sentiment_score":0.5,"model":"Offline Simulation","ragEnabled":true,"agent":"general"}'
```

## Verification

PHP syntax check:

```powershell
Get-ChildItem -Recurse -Filter *.php | ForEach-Object { php -l $_.FullName }
```

JavaScript syntax check if Node.js is installed:

```powershell
Get-ChildItem public\js -Filter *.js | ForEach-Object { node --check $_.FullName }
```

Manual smoke test:

1. Start the server with `php -S localhost:8000 -t public`.
2. Open `/`, `/chat`, and `/visualizer`.
3. Upload, paste, or index a public article URL from Dashboard.
4. Ask a RAG-related question in AI Workspace.
5. Confirm that the answer shows citations and top-k retrieved chunks.
6. Open Vector Space and type a keyword query.
7. Use `Reset demo` on the Dashboard before recording a clean presentation.

## Notes And Limitations

- This is a midterm/demo project, not a production AI platform.
- The default chat engine is an offline simulation.
- Local RAG uses keyword/token chunk scoring, not a full embedding vector database.
- The canvas visualizer represents indexed chunks visually.
- The datastore is a JSON file for portability.
- For production use, replace JSON storage with a real database and store API keys server-side through environment variables.

## Vietnamese Quick Start

Chạy project:

```powershell
cd "D:\Study\HK2_2526\LTWeb-App\Midterm"
php -S localhost:8000 -t public
```

Mở trình duyệt tại:

```text
http://localhost:8000
```

Nếu giao diện chưa cập nhật sau khi sửa CSS/JS, bấm `Ctrl + F5`.
