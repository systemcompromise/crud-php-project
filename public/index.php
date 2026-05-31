<?php
// ============================================
// Monolithic PHP CRUD - Single Entry Point
// IaaS: Docker + Apache + PostgreSQL
// PaaS: Railway deployment
// DNS + SSL: Niagahoster
// ============================================

declare(strict_types=1);

require_once __DIR__ . '/../src/Content.php';

// Handle AJAX / API calls
$action = $_GET['action'] ?? '';
$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if ($isAjax || in_array($action, ['create', 'update', 'delete', 'get'])) {
    header('Content-Type: application/json');
    $content = new Content();

    try {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

        switch ($_SERVER['REQUEST_METHOD']) {
            case 'GET':
                if ($id > 0) {
                    $item = $content->getById($id);
                    if (!$item) { http_response_code(404); echo json_encode(['error' => 'Not found']); exit; }
                    $content->incrementViews($id);
                    echo json_encode(['success' => true, 'data' => $item]);
                } elseif ($action === 'stats') {
                    echo json_encode(['success' => true, 'data' => $content->getStats()]);
                } else {
                    $status = $_GET['status'] ?? '';
                    $search = $_GET['search'] ?? '';
                    echo json_encode(['success' => true, 'data' => $content->getAll($status, $search)]);
                }
                break;

            case 'POST':
                $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
                $errors = validateInput($input);
                if ($errors) { http_response_code(422); echo json_encode(['error' => implode(', ', $errors)]); exit; }
                $item = $content->create($input);
                http_response_code(201);
                echo json_encode(['success' => true, 'data' => $item, 'message' => 'Konten berhasil dibuat']);
                break;

            case 'PUT':
                if (!$id) { http_response_code(400); echo json_encode(['error' => 'ID diperlukan']); exit; }
                $input = json_decode(file_get_contents('php://input'), true);
                $errors = validateInput($input);
                if ($errors) { http_response_code(422); echo json_encode(['error' => implode(', ', $errors)]); exit; }
                $item = $content->update($id, $input);
                echo json_encode(['success' => true, 'data' => $item, 'message' => 'Konten berhasil diperbarui']);
                break;

            case 'DELETE':
                if (!$id) { http_response_code(400); echo json_encode(['error' => 'ID diperlukan']); exit; }
                $content->delete($id);
                echo json_encode(['success' => true, 'message' => 'Konten berhasil dihapus']);
                break;

            default:
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
    }
    exit;
}

function validateInput(?array $data): array {
    $errors = [];
    if (empty($data['title'])) $errors[] = 'Judul wajib diisi';
    if (strlen($data['title'] ?? '') > 255) $errors[] = 'Judul maksimal 255 karakter';
    if (empty($data['body'])) $errors[] = 'Isi konten wajib diisi';
    if (empty($data['author'])) $errors[] = 'Penulis wajib diisi';
    return $errors;
}

// Render HTML
$appUrl = APP_URL ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CMS — Content Manager</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600&family=DM+Mono&display=swap" rel="stylesheet">
<style>
:root {
  --ink:       #0f0e0d;
  --paper:     #f7f4ef;
  --cream:     #ede9e1;
  --rust:      #c8441a;
  --rust-dark: #9e3212;
  --gold:      #c09b4e;
  --sage:      #4a6741;
  --slate:     #4a5568;
  --muted:     #8a8278;
  --border:    #d4cfc7;
  --white:     #ffffff;
  --pub:       #4a6741;
  --draft:     #c09b4e;
  --arch:      #8a8278;
  --shadow-sm: 0 1px 3px rgba(15,14,13,.08);
  --shadow-md: 0 4px 16px rgba(15,14,13,.12);
  --shadow-lg: 0 8px 32px rgba(15,14,13,.16);
  --radius:    6px;
  --transition: all .2s cubic-bezier(.4,0,.2,1);
}

* { box-sizing: border-box; margin: 0; padding: 0; }

body {
  font-family: 'DM Sans', sans-serif;
  background: var(--paper);
  color: var(--ink);
  min-height: 100vh;
  font-size: 14px;
  line-height: 1.6;
}

/* ─── Header ─── */
.header {
  background: var(--ink);
  color: var(--paper);
  padding: 0 24px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  height: 60px;
  position: sticky;
  top: 0;
  z-index: 100;
  box-shadow: var(--shadow-md);
}
.header-logo {
  display: flex;
  align-items: center;
  gap: 10px;
}
.header-logo .logo-mark {
  width: 32px;
  height: 32px;
  background: var(--rust);
  border-radius: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: 'DM Serif Display', serif;
  font-size: 18px;
  color: white;
  font-style: italic;
}
.header-logo h1 {
  font-family: 'DM Serif Display', serif;
  font-size: 20px;
  font-weight: 400;
  letter-spacing: -.3px;
}
.header-logo span {
  color: var(--rust);
}
.header-meta {
  display: flex;
  align-items: center;
  gap: 16px;
  font-size: 12px;
  color: var(--muted);
}
.header-meta .stack-badge {
  background: rgba(255,255,255,.1);
  padding: 4px 10px;
  border-radius: 20px;
  font-family: 'DM Mono', monospace;
  font-size: 11px;
  color: var(--gold);
}
.btn-new {
  background: var(--rust);
  color: white;
  border: none;
  padding: 8px 18px;
  border-radius: var(--radius);
  cursor: pointer;
  font-family: 'DM Sans', sans-serif;
  font-size: 13px;
  font-weight: 500;
  display: flex;
  align-items: center;
  gap: 6px;
  transition: var(--transition);
}
.btn-new:hover { background: var(--rust-dark); transform: translateY(-1px); }

/* ─── Stats Bar ─── */
.stats-bar {
  background: var(--cream);
  border-bottom: 1px solid var(--border);
  padding: 14px 24px;
  display: flex;
  gap: 32px;
  align-items: center;
}
.stat-item {
  display: flex;
  align-items: baseline;
  gap: 6px;
}
.stat-num {
  font-family: 'DM Serif Display', serif;
  font-size: 24px;
  color: var(--ink);
  line-height: 1;
}
.stat-label {
  font-size: 12px;
  color: var(--muted);
  text-transform: uppercase;
  letter-spacing: .5px;
}
.stat-divider {
  width: 1px;
  height: 24px;
  background: var(--border);
}

/* ─── Toolbar ─── */
.toolbar {
  padding: 16px 24px;
  display: flex;
  gap: 10px;
  align-items: center;
  background: var(--white);
  border-bottom: 1px solid var(--border);
  flex-wrap: wrap;
}
.search-wrap {
  flex: 1;
  min-width: 220px;
  position: relative;
}
.search-wrap input {
  width: 100%;
  padding: 8px 12px 8px 36px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  background: var(--paper);
  font-family: 'DM Sans', sans-serif;
  font-size: 13px;
  color: var(--ink);
  transition: var(--transition);
}
.search-wrap input:focus {
  outline: none;
  border-color: var(--rust);
  background: var(--white);
  box-shadow: 0 0 0 3px rgba(200,68,26,.1);
}
.search-icon {
  position: absolute;
  left: 10px;
  top: 50%;
  transform: translateY(-50%);
  color: var(--muted);
  pointer-events: none;
}
.filter-btns {
  display: flex;
  gap: 4px;
}
.filter-btn {
  padding: 7px 14px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  background: var(--paper);
  font-size: 12px;
  font-family: 'DM Sans', sans-serif;
  cursor: pointer;
  color: var(--slate);
  transition: var(--transition);
  font-weight: 500;
}
.filter-btn:hover { border-color: var(--rust); color: var(--rust); }
.filter-btn.active {
  background: var(--ink);
  color: var(--paper);
  border-color: var(--ink);
}

/* ─── Content Grid ─── */
.main {
  padding: 24px;
  max-width: 1200px;
  margin: 0 auto;
}

.content-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 16px;
}

.card {
  background: var(--white);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  overflow: hidden;
  transition: var(--transition);
  box-shadow: var(--shadow-sm);
}
.card:hover {
  box-shadow: var(--shadow-md);
  transform: translateY(-2px);
  border-color: transparent;
}

.card-header {
  padding: 16px 16px 12px;
  border-bottom: 1px solid var(--cream);
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 10px;
}
.card-meta {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}
.badge {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 3px 8px;
  border-radius: 20px;
  font-size: 11px;
  font-weight: 600;
  letter-spacing: .3px;
  text-transform: uppercase;
}
.badge-published { background: #e8f0e5; color: var(--pub); }
.badge-draft     { background: #f5f0e0; color: var(--draft); }
.badge-archived  { background: #f0eeeb; color: var(--arch); }
.badge-category  { background: var(--cream); color: var(--slate); font-weight: 500; }

.card-actions {
  display: flex;
  gap: 2px;
  flex-shrink: 0;
}
.icon-btn {
  width: 28px;
  height: 28px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  background: transparent;
  transition: var(--transition);
}
.icon-btn:hover { background: var(--cream); }
.icon-btn.delete:hover { background: #fee2e2; color: #dc2626; }

.card-body { padding: 12px 16px 16px; }

.card-title {
  font-family: 'DM Serif Display', serif;
  font-size: 17px;
  font-weight: 400;
  line-height: 1.3;
  margin-bottom: 8px;
  color: var(--ink);
}
.card-excerpt {
  font-size: 13px;
  color: var(--slate);
  line-height: 1.6;
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.card-footer {
  padding: 10px 16px;
  background: var(--paper);
  border-top: 1px solid var(--cream);
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-size: 11px;
  color: var(--muted);
}
.card-footer .author {
  display: flex;
  align-items: center;
  gap: 5px;
}
.author-avatar {
  width: 18px;
  height: 18px;
  background: var(--ink);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--paper);
  font-size: 9px;
  font-weight: 600;
  flex-shrink: 0;
}
.card-footer .date-views {
  display: flex;
  align-items: center;
  gap: 10px;
}

/* ─── Empty State ─── */
.empty-state {
  grid-column: 1/-1;
  text-align: center;
  padding: 80px 20px;
  color: var(--muted);
}
.empty-state .empty-icon {
  font-size: 48px;
  margin-bottom: 16px;
  display: block;
}
.empty-state h3 {
  font-family: 'DM Serif Display', serif;
  font-size: 22px;
  font-weight: 400;
  color: var(--ink);
  margin-bottom: 8px;
}
.empty-state p { font-size: 14px; }

/* ─── Modal ─── */
.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(15,14,13,.6);
  z-index: 200;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
  opacity: 0;
  visibility: hidden;
  transition: var(--transition);
}
.modal-backdrop.open {
  opacity: 1;
  visibility: visible;
}
.modal {
  background: var(--white);
  border-radius: 10px;
  width: 100%;
  max-width: 600px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: var(--shadow-lg);
  transform: translateY(20px) scale(.97);
  transition: var(--transition);
}
.modal-backdrop.open .modal {
  transform: translateY(0) scale(1);
}
.modal-header {
  padding: 20px 24px 16px;
  border-bottom: 1px solid var(--border);
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.modal-title {
  font-family: 'DM Serif Display', serif;
  font-size: 20px;
  font-weight: 400;
}
.modal-close {
  width: 32px;
  height: 32px;
  border: none;
  background: var(--cream);
  border-radius: 50%;
  cursor: pointer;
  font-size: 18px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: var(--transition);
}
.modal-close:hover { background: var(--border); }

.modal-body { padding: 20px 24px; }

/* ─── Form ─── */
.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 14px;
}
.form-group {
  margin-bottom: 16px;
}
.form-group:last-child { margin-bottom: 0; }
label {
  display: block;
  font-size: 12px;
  font-weight: 600;
  color: var(--slate);
  text-transform: uppercase;
  letter-spacing: .5px;
  margin-bottom: 6px;
}
input[type=text], select, textarea {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  font-family: 'DM Sans', sans-serif;
  font-size: 14px;
  color: var(--ink);
  background: var(--paper);
  transition: var(--transition);
}
input[type=text]:focus, select:focus, textarea:focus {
  outline: none;
  border-color: var(--rust);
  background: var(--white);
  box-shadow: 0 0 0 3px rgba(200,68,26,.1);
}
textarea {
  resize: vertical;
  min-height: 140px;
  line-height: 1.6;
}

.modal-footer {
  padding: 16px 24px;
  border-top: 1px solid var(--border);
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  background: var(--paper);
}
.btn {
  padding: 9px 20px;
  border-radius: var(--radius);
  font-family: 'DM Sans', sans-serif;
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
  border: 1px solid transparent;
  transition: var(--transition);
}
.btn-secondary {
  background: var(--white);
  border-color: var(--border);
  color: var(--slate);
}
.btn-secondary:hover { border-color: var(--slate); }
.btn-primary {
  background: var(--ink);
  color: var(--paper);
}
.btn-primary:hover { background: #2d2c2a; }
.btn-primary:disabled {
  opacity: .5;
  cursor: not-allowed;
}

/* ─── Toast ─── */
.toast-container {
  position: fixed;
  bottom: 24px;
  right: 24px;
  z-index: 300;
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.toast {
  padding: 12px 20px;
  border-radius: var(--radius);
  font-size: 13px;
  font-weight: 500;
  box-shadow: var(--shadow-md);
  animation: slideUp .3s ease forwards;
  max-width: 320px;
}
.toast-success { background: var(--ink); color: var(--paper); }
.toast-error   { background: #dc2626; color: white; }
@keyframes slideUp {
  from { opacity: 0; transform: translateY(10px); }
  to   { opacity: 1; transform: translateY(0); }
}

/* ─── View Modal ─── */
.view-content-title {
  font-family: 'DM Serif Display', serif;
  font-size: 26px;
  font-weight: 400;
  line-height: 1.3;
  margin-bottom: 12px;
}
.view-content-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-bottom: 20px;
  padding-bottom: 16px;
  border-bottom: 1px solid var(--border);
  font-size: 12px;
  color: var(--muted);
  align-items: center;
}
.view-content-body {
  font-size: 15px;
  line-height: 1.8;
  color: var(--slate);
  white-space: pre-wrap;
}

/* ─── Delete Confirm ─── */
.delete-modal .modal { max-width: 400px; }
.delete-msg { font-size: 14px; color: var(--slate); line-height: 1.6; }
.delete-title { font-weight: 600; color: var(--ink); }
.btn-danger { background: #dc2626; color: white; border: none; }
.btn-danger:hover { background: #b91c1c; }

/* ─── Loader ─── */
.skeleton {
  background: linear-gradient(90deg, var(--cream) 25%, var(--border) 50%, var(--cream) 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
  border-radius: 4px;
}
@keyframes shimmer {
  0% { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}

/* ─── Responsive ─── */
@media (max-width: 640px) {
  .stats-bar { gap: 16px; flex-wrap: wrap; }
  .header-meta .stack-badge { display: none; }
  .form-row { grid-template-columns: 1fr; }
  .content-grid { grid-template-columns: 1fr; }
}
</style>
</head>
<body>

<!-- Header -->
<header class="header">
  <div class="header-logo">
    <div class="logo-mark">C</div>
    <h1>Content<span>Hub</span></h1>
  </div>
  <div class="header-meta">
    <span class="stack-badge">PHP 8.2 · Apache · PostgreSQL · Railway</span>
    <button class="btn-new" onclick="openCreate()">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
      Tambah Konten
    </button>
  </div>
</header>

<!-- Stats Bar -->
<div class="stats-bar">
  <div class="stat-item">
    <span class="stat-num" id="stat-total">–</span>
    <span class="stat-label">Total</span>
  </div>
  <div class="stat-divider"></div>
  <div class="stat-item">
    <span class="stat-num" id="stat-published">–</span>
    <span class="stat-label">Terbit</span>
  </div>
  <div class="stat-divider"></div>
  <div class="stat-item">
    <span class="stat-num" id="stat-draft">–</span>
    <span class="stat-label">Draft</span>
  </div>
  <div class="stat-divider"></div>
  <div class="stat-item">
    <span class="stat-num" id="stat-views">–</span>
    <span class="stat-label">Total Views</span>
  </div>
</div>

<!-- Toolbar -->
<div class="toolbar">
  <div class="search-wrap">
    <svg class="search-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
    <input type="text" id="search-input" placeholder="Cari judul, isi, atau penulis…" oninput="debounceSearch(this.value)">
  </div>
  <div class="filter-btns">
    <button class="filter-btn active" onclick="filterBy('')" data-filter="">Semua</button>
    <button class="filter-btn" onclick="filterBy('published')" data-filter="published">Terbit</button>
    <button class="filter-btn" onclick="filterBy('draft')" data-filter="draft">Draft</button>
    <button class="filter-btn" onclick="filterBy('archived')" data-filter="archived">Arsip</button>
  </div>
</div>

<!-- Main Content -->
<main class="main">
  <div class="content-grid" id="content-grid">
    <!-- Cards rendered via JS -->
  </div>
</main>

<!-- ── MODALS ── -->

<!-- Create/Edit Modal -->
<div class="modal-backdrop" id="form-modal">
  <div class="modal">
    <div class="modal-header">
      <h2 class="modal-title" id="modal-title">Tambah Konten</h2>
      <button class="modal-close" onclick="closeModal('form-modal')">✕</button>
    </div>
    <div class="modal-body">
      <div class="form-group">
        <label for="f-title">Judul <span style="color:var(--rust)">*</span></label>
        <input type="text" id="f-title" placeholder="Masukkan judul konten…" maxlength="255">
      </div>
      <div class="form-row">
        <div class="form-group">
          <label for="f-author">Penulis <span style="color:var(--rust)">*</span></label>
          <input type="text" id="f-author" placeholder="Nama penulis…">
        </div>
        <div class="form-group">
          <label for="f-category">Kategori</label>
          <select id="f-category">
            <option value="Umum">Umum</option>
            <option value="Teknologi">Teknologi</option>
            <option value="Tutorial">Tutorial</option>
            <option value="Pengumuman">Pengumuman</option>
            <option value="Berita">Berita</option>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label for="f-body">Isi Konten <span style="color:var(--rust)">*</span></label>
        <textarea id="f-body" placeholder="Tulis konten di sini…"></textarea>
      </div>
      <div class="form-group">
        <label for="f-status">Status</label>
        <select id="f-status">
          <option value="draft">Draft</option>
          <option value="published">Published</option>
          <option value="archived">Archived</option>
        </select>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('form-modal')">Batal</button>
      <button class="btn btn-primary" id="save-btn" onclick="saveContent()">Simpan</button>
    </div>
  </div>
</div>

<!-- View Modal -->
<div class="modal-backdrop" id="view-modal">
  <div class="modal">
    <div class="modal-header">
      <div></div>
      <button class="modal-close" onclick="closeModal('view-modal')">✕</button>
    </div>
    <div class="modal-body" id="view-content">
      <!-- Filled by JS -->
    </div>
  </div>
</div>

<!-- Delete Confirm Modal -->
<div class="modal-backdrop delete-modal" id="delete-modal">
  <div class="modal">
    <div class="modal-header">
      <h2 class="modal-title">Hapus Konten?</h2>
      <button class="modal-close" onclick="closeModal('delete-modal')">✕</button>
    </div>
    <div class="modal-body">
      <p class="delete-msg">
        Anda yakin ingin menghapus konten <span class="delete-title" id="delete-target-title">"…"</span>?
        Tindakan ini tidak bisa dibatalkan.
      </p>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('delete-modal')">Batal</button>
      <button class="btn btn-danger" id="confirm-delete-btn">Hapus</button>
    </div>
  </div>
</div>

<!-- Toast Container -->
<div class="toast-container" id="toast-container"></div>

<script>
// ─── State ───────────────────────────────────────────────
let state = {
  contents: [],
  filter: '',
  search: '',
  editingId: null,
  deleteId: null,
  searchTimer: null,
};

// ─── API ─────────────────────────────────────────────────
const API = {
  async call(method, params = '', body = null) {
    const opts = {
      method,
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Content-Type': 'application/json',
      }
    };
    if (body) opts.body = JSON.stringify(body);
    const res = await fetch(`/index.php${params}`, opts);
    const data = await res.json();
    if (!res.ok) throw new Error(data.error || 'Terjadi kesalahan');
    return data;
  },

  list:   (s, q) => API.call('GET', `?status=${s}&search=${encodeURIComponent(q)}`),
  get:    (id)   => API.call('GET', `?id=${id}`),
  create: (d)    => API.call('POST', '', d),
  update: (id,d) => API.call('PUT', `?id=${id}`, d),
  delete: (id)   => API.call('DELETE', `?id=${id}`),
  stats:  ()     => API.call('GET', '?action=stats'),
};

// ─── Render ──────────────────────────────────────────────
function statusBadge(s) {
  const map = { published: 'Terbit', draft: 'Draft', archived: 'Arsip' };
  return `<span class="badge badge-${s}">${map[s] || s}</span>`;
}

function initials(name) {
  return name.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2);
}

function formatDate(str) {
  return new Date(str).toLocaleDateString('id-ID', {
    day: 'numeric', month: 'short', year: 'numeric'
  });
}

function renderCards(contents) {
  const grid = document.getElementById('content-grid');
  if (!contents.length) {
    grid.innerHTML = `
      <div class="empty-state">
        <span class="empty-icon">📄</span>
        <h3>Belum ada konten</h3>
        <p>Klik "Tambah Konten" untuk mulai membuat artikel pertama Anda.</p>
      </div>`;
    return;
  }

  grid.innerHTML = contents.map(c => `
    <article class="card" id="card-${c.id}">
      <div class="card-header">
        <div class="card-meta">
          ${statusBadge(c.status)}
          <span class="badge badge-category">${escHtml(c.category)}</span>
        </div>
        <div class="card-actions">
          <button class="icon-btn" onclick="viewContent(${c.id})" title="Lihat">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          </button>
          <button class="icon-btn" onclick="openEdit(${c.id})" title="Edit">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
          </button>
          <button class="icon-btn delete" onclick="confirmDelete(${c.id}, '${escHtml(c.title).replace(/'/g, "\\'")})" title="Hapus">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6m4-6v6"/><path d="M9 6V4h6v2"/></svg>
          </button>
        </div>
      </div>
      <div class="card-body">
        <h3 class="card-title">${escHtml(c.title)}</h3>
        <p class="card-excerpt">${escHtml(c.body)}</p>
      </div>
      <div class="card-footer">
        <span class="author">
          <span class="author-avatar">${initials(c.author)}</span>
          ${escHtml(c.author)}
        </span>
        <span class="date-views">
          <span>${formatDate(c.created_at)}</span>
          <span>👁 ${c.views}</span>
        </span>
      </div>
    </article>
  `).join('');
}

// ─── Load ────────────────────────────────────────────────
async function loadContents() {
  try {
    const res = await API.list(state.filter, state.search);
    state.contents = res.data;
    renderCards(state.contents);
  } catch (e) {
    toast(e.message, 'error');
  }
}

async function loadStats() {
  try {
    const res = await API.stats();
    const d = res.data;
    document.getElementById('stat-total').textContent     = d.total;
    document.getElementById('stat-published').textContent = d.published;
    document.getElementById('stat-draft').textContent     = d.draft;
    document.getElementById('stat-views').textContent     = Number(d.total_views).toLocaleString('id');
  } catch {}
}

// ─── Filters ─────────────────────────────────────────────
function filterBy(val) {
  state.filter = val;
  document.querySelectorAll('.filter-btn').forEach(b => {
    b.classList.toggle('active', b.dataset.filter === val);
  });
  loadContents();
}

function debounceSearch(val) {
  state.search = val;
  clearTimeout(state.searchTimer);
  state.searchTimer = setTimeout(loadContents, 350);
}

// ─── Create / Edit ───────────────────────────────────────
function openCreate() {
  state.editingId = null;
  document.getElementById('modal-title').textContent = 'Tambah Konten';
  document.getElementById('save-btn').textContent = 'Simpan';
  document.getElementById('f-title').value    = '';
  document.getElementById('f-author').value   = '';
  document.getElementById('f-body').value     = '';
  document.getElementById('f-category').value = 'Umum';
  document.getElementById('f-status').value   = 'draft';
  openModal('form-modal');
}

async function openEdit(id) {
  try {
    const res = await API.get(id);
    const c = res.data;
    state.editingId = id;
    document.getElementById('modal-title').textContent = 'Edit Konten';
    document.getElementById('save-btn').textContent = 'Perbarui';
    document.getElementById('f-title').value    = c.title;
    document.getElementById('f-author').value   = c.author;
    document.getElementById('f-body').value     = c.body;
    document.getElementById('f-category').value = c.category;
    document.getElementById('f-status').value   = c.status;
    openModal('form-modal');
  } catch (e) {
    toast(e.message, 'error');
  }
}

async function saveContent() {
  const payload = {
    title:    document.getElementById('f-title').value.trim(),
    author:   document.getElementById('f-author').value.trim(),
    body:     document.getElementById('f-body').value.trim(),
    category: document.getElementById('f-category').value,
    status:   document.getElementById('f-status').value,
  };

  const btn = document.getElementById('save-btn');
  btn.disabled = true;
  btn.textContent = 'Menyimpan…';

  try {
    let res;
    if (state.editingId) {
      res = await API.update(state.editingId, payload);
    } else {
      res = await API.create(payload);
    }
    toast(res.message, 'success');
    closeModal('form-modal');
    await loadContents();
    await loadStats();
  } catch (e) {
    toast(e.message, 'error');
  } finally {
    btn.disabled = false;
    btn.textContent = state.editingId ? 'Perbarui' : 'Simpan';
  }
}

// ─── View ────────────────────────────────────────────────
async function viewContent(id) {
  try {
    const res = await API.get(id);
    const c = res.data;
    document.getElementById('view-content').innerHTML = `
      <h2 class="view-content-title">${escHtml(c.title)}</h2>
      <div class="view-content-meta">
        ${statusBadge(c.status)}
        <span class="badge badge-category">${escHtml(c.category)}</span>
        <span>✍️ ${escHtml(c.author)}</span>
        <span>📅 ${formatDate(c.created_at)}</span>
        <span>👁 ${c.views} views</span>
      </div>
      <div class="view-content-body">${escHtml(c.body)}</div>`;
    openModal('view-modal');
    loadStats(); // update view count
  } catch (e) {
    toast(e.message, 'error');
  }
}

// ─── Delete ──────────────────────────────────────────────
function confirmDelete(id, title) {
  state.deleteId = id;
  document.getElementById('delete-target-title').textContent = `"${title}"`;
  const btn = document.getElementById('confirm-delete-btn');
  btn.onclick = async () => {
    try {
      const res = await API.delete(id);
      toast(res.message, 'success');
      closeModal('delete-modal');
      await loadContents();
      await loadStats();
    } catch (e) {
      toast(e.message, 'error');
    }
  };
  openModal('delete-modal');
}

// ─── Modal helpers ───────────────────────────────────────
function openModal(id) {
  document.getElementById(id).classList.add('open');
  document.body.style.overflow = 'hidden';
}
function closeModal(id) {
  document.getElementById(id).classList.remove('open');
  document.body.style.overflow = '';
}

// Close on backdrop click
document.querySelectorAll('.modal-backdrop').forEach(b => {
  b.addEventListener('click', (e) => {
    if (e.target === b) closeModal(b.id);
  });
});

// ─── Toast ───────────────────────────────────────────────
function toast(msg, type = 'success') {
  const el = document.createElement('div');
  el.className = `toast toast-${type}`;
  el.textContent = msg;
  document.getElementById('toast-container').appendChild(el);
  setTimeout(() => el.remove(), 3500);
}

// ─── Util ────────────────────────────────────────────────
function escHtml(str) {
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

// ─── Init ────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  loadContents();
  loadStats();
});
</script>
</body>
</html>
