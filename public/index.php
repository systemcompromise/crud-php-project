<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/Content.php';

$action = $_GET['action'] ?? '';
$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
$method = $_SERVER['REQUEST_METHOD'];

$methodOverride = $_GET['_method'] ?? '';
if ($methodOverride) {
    $method = strtoupper($methodOverride);
}

if ($isAjax || in_array($action, ['create', 'update', 'delete', 'get', 'stats'])) {
    header('Content-Type: application/json');
    $content = new Content();

    try {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

        switch ($method) {
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
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ContentHub — Kelola Konten</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;1,9..40,400&display=swap" rel="stylesheet">
<style>
:root {
  --c-bg:          #f7f6f3;
  --c-surface:     #ffffff;
  --c-surface-2:   #f2f0ec;
  --c-border:      rgba(0,0,0,.08);
  --c-border-2:    rgba(0,0,0,.14);

  --c-ink-1:       #18171a;
  --c-ink-2:       #4b4851;
  --c-ink-3:       #9390a0;

  --c-brand:       #1a1a2e;
  --c-brand-h:     #0f0f1e;
  --c-brand-tint:  #eeeef7;

  --c-pub:         #0a6640;
  --c-pub-bg:      #e6f5ee;
  --c-draft:       #7a4c00;
  --c-draft-bg:    #fff4e0;
  --c-arc:         #4b4851;
  --c-arc-bg:      #f0eeec;
  --c-red:         #c0392b;
  --c-red-bg:      #fdf0ee;

  --r-xs:  4px;
  --r-sm:  8px;
  --r-md:  12px;
  --r-lg:  16px;
  --r-xl:  24px;

  --t-fast:   150ms cubic-bezier(.4,0,.2,1);
  --t-mid:    250ms cubic-bezier(.4,0,.2,1);
}

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html { scroll-behavior: smooth; }

body {
  font-family: 'DM Sans', sans-serif;
  background: var(--c-bg);
  color: var(--c-ink-1);
  min-height: 100vh;
  font-size: 14px;
  line-height: 1.65;
  -webkit-font-smoothing: antialiased;
}

.topbar {
  position: sticky;
  top: 0;
  z-index: 100;
  height: 56px;
  background: rgba(247,246,243,.9);
  backdrop-filter: blur(14px);
  -webkit-backdrop-filter: blur(14px);
  border-bottom: 1px solid var(--c-border);
  display: flex;
  align-items: center;
  padding: 0 24px;
  gap: 16px;
}

.brand {
  display: flex;
  align-items: center;
  gap: 9px;
  text-decoration: none;
  color: inherit;
}
.brand-mark {
  width: 28px;
  height: 28px;
  background: var(--c-brand);
  border-radius: var(--r-sm);
  display: grid;
  place-items: center;
  flex-shrink: 0;
}
.brand-name {
  font-family: 'DM Serif Display', serif;
  font-size: 16px;
  letter-spacing: -.2px;
  color: var(--c-ink-1);
}
.brand-name em { color: var(--c-ink-3); font-style: normal; font-weight: 400; }

.topbar-spacer { flex: 1; }

.stack-badge {
  font-size: 11px;
  font-weight: 400;
  color: var(--c-ink-3);
  background: var(--c-surface-2);
  border: 1px solid var(--c-border);
  border-radius: 20px;
  padding: 3px 10px;
  letter-spacing: .1px;
}

.btn-new {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  height: 34px;
  padding: 0 14px;
  background: var(--c-brand);
  color: #fff;
  border: none;
  border-radius: var(--r-sm);
  font-family: 'DM Sans', sans-serif;
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
  transition: background var(--t-fast), box-shadow var(--t-fast);
  white-space: nowrap;
}
.btn-new:hover {
  background: var(--c-brand-h);
  box-shadow: 0 4px 12px rgba(26,26,46,.22);
}
.btn-new svg { flex-shrink: 0; }

.shell {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 24px 48px;
}

.page-header {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 20px;
  flex-wrap: wrap;
  padding: 32px 0 24px;
  border-bottom: 1px solid var(--c-border);
  margin-bottom: 20px;
}

.page-header h1 {
  font-family: 'DM Serif Display', serif;
  font-size: 28px;
  font-weight: 400;
  letter-spacing: -.4px;
  line-height: 1.2;
  color: var(--c-ink-1);
}
.page-header p {
  font-size: 13px;
  color: var(--c-ink-3);
  margin-top: 3px;
}

.stats-cluster {
  display: flex;
  background: var(--c-surface);
  border: 1px solid var(--c-border);
  border-radius: var(--r-md);
  overflow: hidden;
}
.stat-cell {
  padding: 10px 20px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1px;
  min-width: 68px;
  transition: background var(--t-fast);
}
.stat-cell:hover { background: var(--c-surface-2); }
.stat-cell + .stat-cell { border-left: 1px solid var(--c-border); }
.stat-n {
  font-family: 'DM Serif Display', serif;
  font-size: 22px;
  font-weight: 400;
  color: var(--c-ink-1);
  line-height: 1;
}
.stat-l {
  font-size: 10px;
  color: var(--c-ink-3);
  text-transform: uppercase;
  letter-spacing: .7px;
}

.control-bar {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 20px;
  flex-wrap: wrap;
}

.search-wrap {
  flex: 1;
  min-width: 180px;
  max-width: 340px;
}

.search-wrap input {
  width: 100%;
  height: 36px;
  padding: 0 12px 0 36px;
  border: 1px solid var(--c-border);
  border-radius: var(--r-sm);
  background-color: var(--c-surface);
  background-image: url("data:image/svg+xml,%3Csvg width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%239390a0' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' xmlns='http://www.w3.org/2000/svg'%3E%3Ccircle cx='11' cy='11' r='8'/%3E%3Cpath d='m21 21-4.35-4.35'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: 11px center;
  background-size: 14px 14px;
  font-family: 'DM Sans', sans-serif;
  font-size: 13px;
  color: var(--c-ink-1);
  transition: border-color var(--t-fast), box-shadow var(--t-fast);
}
.search-wrap input::placeholder { color: var(--c-ink-3); }
.search-wrap input:focus {
  outline: none;
  border-color: var(--c-brand);
  box-shadow: 0 0 0 3px rgba(26,26,46,.08);
}

.seg-group {
  display: flex;
  background: var(--c-surface);
  border: 1px solid var(--c-border);
  border-radius: var(--r-sm);
  padding: 3px;
  gap: 2px;
}
.seg-btn {
  height: 28px;
  padding: 0 12px;
  border: none;
  border-radius: 5px;
  background: transparent;
  font-family: 'DM Sans', sans-serif;
  font-size: 12px;
  font-weight: 400;
  color: var(--c-ink-2);
  cursor: pointer;
  transition: background var(--t-fast), color var(--t-fast);
  white-space: nowrap;
}
.seg-btn:hover { background: var(--c-surface-2); }
.seg-btn.active {
  background: var(--c-brand);
  color: #fff;
  font-weight: 500;
}

.card-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(290px, 1fr));
  gap: 14px;
}

.card {
  background: var(--c-surface);
  border: 1px solid var(--c-border);
  border-radius: var(--r-lg);
  overflow: hidden;
  display: flex;
  flex-direction: column;
  transition: transform var(--t-mid), box-shadow var(--t-mid), border-color var(--t-mid);
  animation: rise .3s cubic-bezier(.4,0,.2,1) both;
}
.card:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 24px rgba(0,0,0,.07);
  border-color: var(--c-border-2);
}

@keyframes rise {
  from { opacity: 0; transform: translateY(10px); }
  to   { opacity: 1; transform: translateY(0); }
}

.card-body {
  padding: 16px 16px 12px;
  flex: 1;
}

.card-meta-top {
  display: flex;
  align-items: center;
  gap: 6px;
  margin-bottom: 10px;
}

.pill {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-size: 10px;
  font-weight: 500;
  letter-spacing: .4px;
  text-transform: uppercase;
  padding: 2px 7px;
  border-radius: var(--r-xs);
}
.pill-pub      { background: var(--c-pub-bg);   color: var(--c-pub);   }
.pill-draft    { background: var(--c-draft-bg); color: var(--c-draft); }
.pill-archived { background: var(--c-arc-bg);   color: var(--c-arc);   }
.pill-cat      { background: var(--c-brand-tint); color: var(--c-brand); font-size: 11px; text-transform: none; letter-spacing: 0; padding: 2px 8px; }

.card-actions {
  margin-left: auto;
  display: flex;
  align-items: center;
  gap: 0;
  opacity: 0;
  transition: opacity var(--t-fast);
}
.card:hover .card-actions { opacity: 1; }

.ic-btn {
  width: 28px;
  height: 28px;
  border: none;
  border-radius: var(--r-xs);
  background: transparent;
  color: var(--c-ink-3);
  cursor: pointer;
  display: grid;
  place-items: center;
  transition: background var(--t-fast), color var(--t-fast);
}
.ic-btn:hover         { background: var(--c-brand-tint); color: var(--c-brand); }
.ic-btn.del:hover     { background: var(--c-red-bg); color: var(--c-red); }

.card-title {
  font-family: 'DM Serif Display', serif;
  font-size: 15px;
  font-weight: 400;
  line-height: 1.45;
  color: var(--c-ink-1);
  letter-spacing: -.1px;
  margin-bottom: 7px;
}

.card-excerpt {
  font-size: 13px;
  color: var(--c-ink-2);
  line-height: 1.7;
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.card-footer {
  padding: 10px 16px;
  border-top: 1px solid var(--c-border);
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
}

.card-author {
  display: flex;
  align-items: center;
  gap: 7px;
}
.avatar {
  width: 22px;
  height: 22px;
  border-radius: 50%;
  background: var(--c-brand);
  color: #fff;
  font-size: 8px;
  font-weight: 600;
  display: grid;
  place-items: center;
  letter-spacing: .5px;
  flex-shrink: 0;
}
.author-name { font-size: 12px; color: var(--c-ink-2); font-weight: 400; }

.card-info {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 11px;
  color: var(--c-ink-3);
}
.card-info span {
  display: flex;
  align-items: center;
  gap: 3px;
}

.empty {
  grid-column: 1 / -1;
  text-align: center;
  padding: 72px 24px;
}
.empty-circle {
  width: 52px;
  height: 52px;
  background: var(--c-surface-2);
  border: 1px solid var(--c-border);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 14px;
  color: var(--c-ink-3);
}
.empty h3 {
  font-family: 'DM Serif Display', serif;
  font-size: 18px;
  font-weight: 400;
  color: var(--c-ink-1);
  margin-bottom: 5px;
}
.empty p { font-size: 13px; color: var(--c-ink-3); }

.overlay {
  position: fixed;
  inset: 0;
  background: rgba(15,14,18,.4);
  backdrop-filter: blur(3px);
  -webkit-backdrop-filter: blur(3px);
  z-index: 200;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
  opacity: 0;
  visibility: hidden;
  transition: opacity var(--t-mid), visibility var(--t-mid);
}
.overlay.open {
  opacity: 1;
  visibility: visible;
}
.overlay.open .drawer {
  transform: translateY(0);
  opacity: 1;
}

.drawer {
  background: var(--c-surface);
  border-radius: var(--r-xl);
  width: 100%;
  max-width: 540px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 24px 64px rgba(0,0,0,.16);
  transform: translateY(20px);
  opacity: 0;
  transition: transform var(--t-mid), opacity var(--t-mid);
}

.drawer-head {
  padding: 20px 22px 16px;
  border-bottom: 1px solid var(--c-border);
  display: flex;
  align-items: center;
  justify-content: space-between;
  position: sticky;
  top: 0;
  background: var(--c-surface);
  z-index: 1;
  border-radius: var(--r-xl) var(--r-xl) 0 0;
}
.drawer-title {
  font-family: 'DM Serif Display', serif;
  font-size: 17px;
  font-weight: 400;
  color: var(--c-ink-1);
}
.close-btn {
  width: 28px;
  height: 28px;
  border: 1px solid var(--c-border);
  background: var(--c-surface-2);
  border-radius: 50%;
  cursor: pointer;
  display: grid;
  place-items: center;
  color: var(--c-ink-2);
  font-size: 14px;
  line-height: 1;
  transition: background var(--t-fast);
}
.close-btn:hover { background: var(--c-border); }

.drawer-body { padding: 20px 22px; }

.field { margin-bottom: 16px; }
.field:last-child { margin-bottom: 0; }

.field-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}

label {
  display: block;
  font-size: 11px;
  font-weight: 500;
  color: var(--c-ink-3);
  text-transform: uppercase;
  letter-spacing: .6px;
  margin-bottom: 5px;
}
label .req { color: var(--c-brand); }

input[type=text],
select,
textarea {
  width: 100%;
  padding: 9px 12px;
  border: 1px solid var(--c-border);
  border-radius: var(--r-sm);
  background: var(--c-bg);
  font-family: 'DM Sans', sans-serif;
  font-size: 13px;
  color: var(--c-ink-1);
  transition: border-color var(--t-fast), box-shadow var(--t-fast), background var(--t-fast);
  appearance: none;
  -webkit-appearance: none;
}
input[type=text]:focus,
select:focus,
textarea:focus {
  outline: none;
  border-color: var(--c-brand);
  background: var(--c-surface);
  box-shadow: 0 0 0 3px rgba(26,26,46,.07);
}
textarea {
  resize: vertical;
  min-height: 120px;
  line-height: 1.7;
}
input[type=text]::placeholder,
textarea::placeholder { color: var(--c-ink-3); }

select {
  background-image: url("data:image/svg+xml,%3Csvg width='12' height='8' viewBox='0 0 12 8' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M1 1L6 7L11 1' stroke='%239390a0' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 12px center;
  padding-right: 32px;
  cursor: pointer;
}

.drawer-foot {
  padding: 14px 22px;
  border-top: 1px solid var(--c-border);
  display: flex;
  justify-content: flex-end;
  gap: 8px;
  background: var(--c-surface-2);
  border-radius: 0 0 var(--r-xl) var(--r-xl);
}

.btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  height: 36px;
  padding: 0 16px;
  border-radius: var(--r-sm);
  font-family: 'DM Sans', sans-serif;
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
  border: 1px solid transparent;
  transition: all var(--t-fast);
}
.btn-ghost {
  background: transparent;
  border-color: var(--c-border);
  color: var(--c-ink-2);
}
.btn-ghost:hover { background: var(--c-surface-2); border-color: var(--c-border-2); }
.btn-primary {
  background: var(--c-brand);
  color: #fff;
}
.btn-primary:hover { background: var(--c-brand-h); box-shadow: 0 4px 12px rgba(26,26,46,.2); }
.btn-primary:disabled { opacity: .45; cursor: not-allowed; box-shadow: none; }
.btn-danger {
  background: var(--c-red);
  color: #fff;
}
.btn-danger:hover { background: #a93226; }

.view-title {
  font-family: 'DM Serif Display', serif;
  font-size: 22px;
  font-weight: 400;
  line-height: 1.35;
  letter-spacing: -.3px;
  color: var(--c-ink-1);
  margin-bottom: 12px;
}
.view-chips {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  align-items: center;
  padding-bottom: 14px;
  border-bottom: 1px solid var(--c-border);
  margin-bottom: 18px;
}
.view-chips .dot {
  width: 3px; height: 3px;
  border-radius: 50%;
  background: var(--c-border-2);
}
.view-chips span { font-size: 12px; color: var(--c-ink-3); }
.view-content {
  font-size: 14px;
  line-height: 1.85;
  color: var(--c-ink-2);
  white-space: pre-wrap;
}

.del-drawer .drawer { max-width: 400px; }
.del-box {
  display: flex;
  gap: 14px;
  align-items: flex-start;
}
.del-icon {
  width: 38px;
  height: 38px;
  flex-shrink: 0;
  background: var(--c-red-bg);
  border-radius: var(--r-md);
  display: grid;
  place-items: center;
  color: var(--c-red);
}
.del-text h4 {
  font-size: 14px;
  font-weight: 500;
  color: var(--c-ink-1);
  margin-bottom: 4px;
}
.del-text p {
  font-size: 13px;
  color: var(--c-ink-2);
  line-height: 1.6;
}

.toast-stack {
  position: fixed;
  bottom: 20px;
  right: 20px;
  z-index: 500;
  display: flex;
  flex-direction: column;
  gap: 6px;
  pointer-events: none;
}
.toast {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 16px;
  border-radius: var(--r-md);
  font-size: 13px;
  font-weight: 400;
  box-shadow: 0 6px 20px rgba(0,0,0,.12);
  animation: pop-in .2s cubic-bezier(.4,0,.2,1) forwards;
  pointer-events: all;
  max-width: 280px;
}
.toast-ok  { background: var(--c-ink-1); color: #f7f6f3; }
.toast-err { background: var(--c-red);   color: #fff; }
@keyframes pop-in {
  from { opacity: 0; transform: translateY(6px) scale(.97); }
  to   { opacity: 1; transform: none; }
}

@media (max-width: 600px) {
  .topbar     { padding: 0 16px; }
  .shell      { padding: 0 16px 40px; }
  .stack-badge { display: none; }
  .page-header { padding: 20px 0 16px; flex-direction: column; align-items: flex-start; gap: 14px; }
  .card-grid  { grid-template-columns: 1fr; }
  .field-row  { grid-template-columns: 1fr; }
  .stats-cluster { width: 100%; }
  .stat-cell  { flex: 1; }
}
</style>
</head>
<body>

<nav class="topbar">
  <a class="brand" href="#">
    <div class="brand-mark">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round">
        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
        <polyline points="14 2 14 8 20 8"/>
        <line x1="16" y1="13" x2="8" y2="13"/>
        <line x1="16" y1="17" x2="8" y2="17"/>
      </svg>
    </div>
    <span class="brand-name">Content<em>Hub</em></span>
  </a>
  <div class="topbar-spacer"></div>
  <span class="stack-badge">PHP 8.2 · Apache · PostgreSQL · Railway</span>
  <button class="btn-new" onclick="openCreate()">
    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
      <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
    </svg>
    Tambah Konten
  </button>
</nav>

<div class="shell">

  <div class="page-header">
    <div>
      <h1>Kelola Konten</h1>
      <p>Buat, sunting, dan terbitkan artikel Anda</p>
    </div>
    <div class="stats-cluster">
      <div class="stat-cell">
        <span class="stat-n" id="s-total">–</span>
        <span class="stat-l">Total</span>
      </div>
      <div class="stat-cell">
        <span class="stat-n" id="s-pub">–</span>
        <span class="stat-l">Terbit</span>
      </div>
      <div class="stat-cell">
        <span class="stat-n" id="s-draft">–</span>
        <span class="stat-l">Draft</span>
      </div>
      <div class="stat-cell">
        <span class="stat-n" id="s-views">–</span>
        <span class="stat-l">Views</span>
      </div>
    </div>
  </div>

  <div class="control-bar">
    <div class="search-wrap">
      <input type="text" id="q" placeholder="Cari judul, isi, atau penulis…" oninput="debounce(this.value)">
    </div>
    <div class="seg-group">
      <button class="seg-btn active" data-f="" onclick="setFilter(this,'')">Semua</button>
      <button class="seg-btn" data-f="published" onclick="setFilter(this,'published')">Terbit</button>
      <button class="seg-btn" data-f="draft" onclick="setFilter(this,'draft')">Draft</button>
      <button class="seg-btn" data-f="archived" onclick="setFilter(this,'archived')">Arsip</button>
    </div>
  </div>

  <div class="card-grid" id="grid"></div>

</div>

<!-- FORM MODAL -->
<div class="overlay" id="m-form">
  <div class="drawer">
    <div class="drawer-head">
      <h2 class="drawer-title" id="m-form-title">Tambah Konten</h2>
      <button class="close-btn" onclick="close_('m-form')" aria-label="Tutup">✕</button>
    </div>
    <div class="drawer-body">
      <div class="field">
        <label>Judul <span class="req">*</span></label>
        <input type="text" id="f-title" placeholder="Tulis judul konten…" maxlength="255">
      </div>
      <div class="field-row">
        <div class="field">
          <label>Penulis <span class="req">*</span></label>
          <input type="text" id="f-author" placeholder="Nama penulis…">
        </div>
        <div class="field">
          <label>Kategori</label>
          <select id="f-cat">
            <option value="Umum">Umum</option>
            <option value="Teknologi">Teknologi</option>
            <option value="Tutorial">Tutorial</option>
            <option value="Pengumuman">Pengumuman</option>
            <option value="Berita">Berita</option>
          </select>
        </div>
      </div>
      <div class="field">
        <label>Isi Konten <span class="req">*</span></label>
        <textarea id="f-body" placeholder="Tulis konten di sini…"></textarea>
      </div>
      <div class="field">
        <label>Status</label>
        <select id="f-status">
          <option value="draft">Draft</option>
          <option value="published">Published</option>
          <option value="archived">Archived</option>
        </select>
      </div>
    </div>
    <div class="drawer-foot">
      <button class="btn btn-ghost" onclick="close_('m-form')">Batal</button>
      <button class="btn btn-primary" id="save-btn" onclick="saveContent()">Simpan</button>
    </div>
  </div>
</div>

<!-- VIEW MODAL -->
<div class="overlay" id="m-view">
  <div class="drawer">
    <div class="drawer-head">
      <div></div>
      <button class="close-btn" onclick="close_('m-view')" aria-label="Tutup">✕</button>
    </div>
    <div class="drawer-body" id="view-body"></div>
  </div>
</div>

<!-- DELETE MODAL -->
<div class="overlay del-drawer" id="m-del">
  <div class="drawer">
    <div class="drawer-head">
      <h2 class="drawer-title">Hapus Konten</h2>
      <button class="close-btn" onclick="close_('m-del')" aria-label="Tutup">✕</button>
    </div>
    <div class="drawer-body">
      <div class="del-box">
        <div class="del-icon">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
            <polyline points="3 6 5 6 21 6"/>
            <path d="M19 6l-1 14H6L5 6"/>
            <path d="M10 11v6m4-6v6"/>
            <path d="M9 6V4h6v2"/>
          </svg>
        </div>
        <div class="del-text">
          <h4>Yakin ingin menghapus?</h4>
          <p>Konten <strong id="del-name">ini</strong> akan dihapus permanen dan tidak dapat dipulihkan.</p>
        </div>
      </div>
    </div>
    <div class="drawer-foot">
      <button class="btn btn-ghost" onclick="close_('m-del')">Batal</button>
      <button class="btn btn-danger" id="del-confirm">Hapus</button>
    </div>
  </div>
</div>

<div class="toast-stack" id="toasts"></div>

<script>
const S = { filter: '', search: '', editId: null, timer: null };

const api = {
  req(method, qs = '', body = null) {
    const url = '/index.php' + qs;
    const opts = {
      method,
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: body ? JSON.stringify(body) : null,
    };

    if (method === 'DELETE' || method === 'PUT') {
      const sep = qs.includes('?') ? '&' : '?';
      return fetch(url + sep + '_method=' + method, {
        ...opts,
        method: 'POST',
      }).then(async r => {
        const d = await r.json();
        if (!r.ok) throw new Error(d.error || 'Terjadi kesalahan');
        return d;
      });
    }

    return fetch(url, opts).then(async r => {
      const d = await r.json();
      if (!r.ok) throw new Error(d.error || 'Terjadi kesalahan');
      return d;
    });
  },
  list:   ()      => api.req('GET', `?status=${S.filter}&search=${encodeURIComponent(S.search)}`),
  get:    id      => api.req('GET', `?id=${id}`),
  stats:  ()      => api.req('GET', '?action=stats'),
  create: data    => api.req('POST', '', data),
  update: (id, d) => api.req('PUT', `?id=${id}`, d),
  delete: id      => api.req('DELETE', `?id=${id}`),
};

const $ = id => document.getElementById(id);
const esc = s => String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');

function fmt(d) {
  return new Date(d).toLocaleDateString('id-ID', { day:'numeric', month:'short', year:'numeric' });
}
function ini(n) {
  return n.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2);
}
function statusPill(s) {
  const map = { published: ['pub','Terbit'], draft: ['draft','Draft'], archived: ['archived','Arsip'] };
  const [cls, lbl] = map[s] || ['archived', s];
  return `<span class="pill pill-${cls}">${lbl}</span>`;
}

function render(list) {
  if (!list.length) {
    $('grid').innerHTML = `
      <div class="empty">
        <div class="empty-circle">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
            <polyline points="14 2 14 8 20 8"/>
          </svg>
        </div>
        <h3>Belum ada konten</h3>
        <p>Mulai dengan membuat artikel pertama Anda</p>
      </div>`;
    return;
  }

  $('grid').innerHTML = list.map((c, i) => `
    <div class="card" style="animation-delay:${i * 35}ms">
      <div class="card-body">
        <div class="card-meta-top">
          ${statusPill(c.status)}
          <span class="pill pill-cat">${esc(c.category)}</span>
          <div class="card-actions">
            <button class="ic-btn" onclick="viewItem(${c.id})" title="Lihat">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            </button>
            <button class="ic-btn" onclick="openEdit(${c.id})" title="Sunting">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4z"/></svg>
            </button>
            <button class="ic-btn del" onclick="confirmDel(${c.id},'${esc(c.title).replace(/'/g,"\\'")}')" title="Hapus">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6m4-6v6"/><path d="M9 6V4h6v2"/></svg>
            </button>
          </div>
        </div>
        <h3 class="card-title">${esc(c.title)}</h3>
        <p class="card-excerpt">${esc(c.body)}</p>
      </div>
      <div class="card-footer">
        <div class="card-author">
          <div class="avatar">${ini(c.author)}</div>
          <span class="author-name">${esc(c.author)}</span>
        </div>
        <div class="card-info">
          <span>${fmt(c.created_at)}</span>
          <span>
            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            ${c.views}
          </span>
        </div>
      </div>
    </div>
  `).join('');
}

async function load() {
  try {
    const r = await api.list();
    render(r.data);
  } catch(e) { toast(e.message, 'err'); }
}

async function loadStats() {
  try {
    const r = await api.stats();
    const d = r.data;
    $('s-total').textContent = d.total;
    $('s-pub').textContent   = d.published;
    $('s-draft').textContent = d.draft;
    $('s-views').textContent = Number(d.total_views).toLocaleString('id');
  } catch {}
}

function setFilter(btn, val) {
  S.filter = val;
  document.querySelectorAll('.seg-btn').forEach(b => b.classList.toggle('active', b === btn));
  load();
}

function debounce(val) {
  S.search = val;
  clearTimeout(S.timer);
  S.timer = setTimeout(load, 320);
}

function openCreate() {
  S.editId = null;
  $('m-form-title').textContent = 'Tambah Konten';
  $('save-btn').textContent = 'Simpan';
  $('f-title').value  = '';
  $('f-author').value = '';
  $('f-body').value   = '';
  $('f-cat').value    = 'Umum';
  $('f-status').value = 'draft';
  open_('m-form');
}

async function openEdit(id) {
  try {
    const r = await api.get(id);
    const c = r.data;
    S.editId = id;
    $('m-form-title').textContent = 'Sunting Konten';
    $('save-btn').textContent = 'Perbarui';
    $('f-title').value  = c.title;
    $('f-author').value = c.author;
    $('f-body').value   = c.body;
    $('f-cat').value    = c.category;
    $('f-status').value = c.status;
    open_('m-form');
  } catch(e) { toast(e.message, 'err'); }
}

async function saveContent() {
  const payload = {
    title:    $('f-title').value.trim(),
    author:   $('f-author').value.trim(),
    body:     $('f-body').value.trim(),
    category: $('f-cat').value,
    status:   $('f-status').value,
  };
  const btn = $('save-btn');
  btn.disabled = true;
  btn.textContent = '…';
  try {
    const r = S.editId
      ? await api.update(S.editId, payload)
      : await api.create(payload);
    toast(r.message);
    close_('m-form');
    await load(); await loadStats();
  } catch(e) { toast(e.message, 'err'); }
  finally {
    btn.disabled = false;
    btn.textContent = S.editId ? 'Perbarui' : 'Simpan';
  }
}

async function viewItem(id) {
  try {
    const r = await api.get(id);
    const c = r.data;
    $('view-body').innerHTML = `
      <p class="view-title">${esc(c.title)}</p>
      <div class="view-chips">
        ${statusPill(c.status)}
        <span class="dot"></span>
        <span>${esc(c.category)}</span>
        <span class="dot"></span>
        <span>${esc(c.author)}</span>
        <span class="dot"></span>
        <span>${fmt(c.created_at)}</span>
        <span class="dot"></span>
        <span>${c.views} views</span>
      </div>
      <div class="view-content">${esc(c.body)}</div>`;
    open_('m-view');
    loadStats();
  } catch(e) { toast(e.message, 'err'); }
}

function confirmDel(id, title) {
  $('del-name').textContent = `"${title}"`;
  $('del-confirm').onclick = async () => {
    try {
      const r = await api.delete(id);
      toast(r.message);
      close_('m-del');
      await load(); await loadStats();
    } catch(e) { toast(e.message, 'err'); }
  };
  open_('m-del');
}

function open_(id)  { $(id).classList.add('open');    document.body.style.overflow = 'hidden'; }
function close_(id) { $(id).classList.remove('open'); document.body.style.overflow = ''; }

document.querySelectorAll('.overlay').forEach(b => {
  b.addEventListener('click', e => { if (e.target === b) close_(b.id); });
});
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') document.querySelectorAll('.overlay.open').forEach(b => close_(b.id));
});

function toast(msg, type = 'ok') {
  const el = document.createElement('div');
  el.className = `toast toast-${type}`;
  el.innerHTML = type === 'ok'
    ? `<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="20 6 9 17 4 12"/></svg> ${esc(msg)}`
    : `<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg> ${esc(msg)}`;
  $('toasts').appendChild(el);
  setTimeout(() => el.remove(), 3200);
}

document.addEventListener('DOMContentLoaded', () => {
  load();
  loadStats();
});
</script>
</body>
</html>