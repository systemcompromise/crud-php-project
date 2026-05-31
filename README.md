# 🚀 PHP CRUD CMS — Monolithic Architecture

**Stack:** PHP 8.2 · Apache · PostgreSQL · Docker · Railway (PaaS)

---

## 🏗️ Arsitektur Sistem

```
┌─────────────────────────────────────────────────────────────┐
│                    ARSITEKTUR MONOLITIK                      │
├──────────────────┬──────────────────────────────────────────┤
│  IaaS Layer      │  Docker container mengelola:             │
│  (Infrastructure)│  - OS (Debian via PHP-Apache image)      │
│                  │  - PHP 8.2 runtime                       │
│                  │  - Apache web server                     │
├──────────────────┼──────────────────────────────────────────┤
│  PaaS Layer      │  Railway mengelola:                      │
│  (Platform)      │  - Container orchestration               │
│                  │  - Auto-deploy dari GitHub               │
│                  │  - Managed PostgreSQL service            │
│                  │  - SSL termination (*.up.railway.app)    │
│                  │  - Environment variables                 │
│                  │  - Dynamic PORT injection                │
└──────────────────┴──────────────────────────────────────────┘
```

---

## 📁 Struktur Project

```
crud-php-project/
├── Dockerfile              ← Build image PHP 8.2 + Apache
├── docker-compose.yml      ← Development lokal saja
├── .gitignore
├── config/
│   └── database.php        ← Konfigurasi koneksi PostgreSQL
├── src/
│   ├── Database.php        ← Singleton PDO connection
│   └── Content.php         ← Model CRUD
├── public/
│   ├── index.php           ← Entry point: routing + HTML + API
│   └── .htaccess           ← Apache URL rewriting + HTTPS redirect
└── docker/
    ├── apache.conf         ← Virtual host config (DocumentRoot: /var/www/html/public)
    ├── php.ini             ← PHP production settings
    ├── entrypoint.sh       ← Fix MPM conflict + handle Railway dynamic PORT
    └── init.sql            ← Schema + trigger + index + seed data PostgreSQL
```

---

## 🛠️ Langkah 1: Install Docker (Development Lokal)

### Windows
```bash
# Download Docker Desktop
https://www.docker.com/products/docker-desktop/

# Atau via winget
winget install Docker.DockerDesktop
```

### Ubuntu/Debian
```bash
sudo apt-get update
sudo apt-get install -y ca-certificates curl gnupg

sudo install -m 0755 -d /etc/apt/keyrings
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | \
  sudo gpg --dearmor -o /etc/apt/keyrings/docker.gpg

echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] \
  https://download.docker.com/linux/ubuntu $(. /etc/os-release && echo "$VERSION_CODENAME") stable" | \
  sudo tee /etc/apt/sources.list.d/docker.list

sudo apt-get update
sudo apt-get install -y docker-ce docker-ce-cli containerd.io docker-compose-plugin

sudo usermod -aG docker $USER
newgrp docker

docker --version
docker compose version
```

---

## 🐳 Langkah 2: Jalankan Secara Lokal

```bash
# Clone project
git clone https://github.com/systemcompromise/crud-php-project.git
cd crud-php-project

# Build & jalankan semua service (app + PostgreSQL lokal)
docker compose up --build

# Akses di: http://localhost:8080
```

> `docker-compose.yml` menyediakan service `app` (PHP+Apache) dan `db` (PostgreSQL lokal).
> Database akan diinisialisasi otomatis dari `docker/init.sql` saat container pertama kali dijalankan.

---

## ☁️ Langkah 3: Deploy ke Railway (PaaS)

### A. Push ke GitHub

Pastikan seluruh kode sudah di-commit dan di-push ke repository GitHub Anda.

```bash
git add .
git commit -m "initial commit"
git push origin main
```

### B. Setup Railway Project

1. Login di [railway.app](https://railway.app)
2. Klik **New Project** → **Deploy from GitHub repo**
3. Pilih repository `crud-php-project`
4. Railway otomatis mendeteksi `Dockerfile` dan memulai build

### C. Tambah PostgreSQL Database

Di Railway dashboard:
1. Klik **+ New** di dalam project yang sama
2. Pilih **Database** → **Add PostgreSQL**
3. Tunggu hingga status Postgres menjadi **Online**

Railway **tidak otomatis** menyuntikkan `DATABASE_URL` ke service PHP — Anda perlu menautkannya secara manual (lihat langkah D).

### D. Set Environment Variables di Service PHP

Klik service **crud-php-project** → tab **Variables** → tambahkan:

| Key | Value |
|-----|-------|
| `DATABASE_URL` | Salin dari Postgres → **Variables** → `DATABASE_URL` |
| `APP_ENV` | `production` |
| `APP_URL` | URL Railway yang di-generate (lihat langkah E) |

> Cara salin `DATABASE_URL` dari Postgres: klik service **Postgres** → tab **Variables** → copy nilai `DATABASE_URL`.
>
> Untuk koneksi internal (lebih cepat, tanpa egress cost): gunakan URL dari tab **Connect → Private Network** pada service Postgres, formatnya `postgresql://postgres:PASSWORD@postgres.railway.internal:5432/railway`.

### E. Inisialisasi Skema Database

Sebelum aplikasi bisa digunakan, jalankan `init.sql` untuk membuat tabel `contents`.

**Opsi 1 — via terminal lokal (perlu psql terinstall):**
```bash
PGPASSWORD=<PASSWORD> psql \
  -h zephyr.proxy.rlwy.net \
  -U postgres \
  -p 10531 \
  -d railway \
  -f docker/init.sql
```

**Opsi 2 — via Railway Dashboard:**
1. Klik service **Postgres** → tab **Database** → **Data**
2. Buka Query editor, paste seluruh isi `docker/init.sql`, lalu jalankan

### F. Generate Domain Railway

Di Railway → klik service **crud-php-project** → **Settings** → **Domains** → **Generate Domain**

Anda akan mendapat URL seperti:
```
https://crud-php-project-production.up.railway.app
```

Salin URL ini, kemudian update variable `APP_URL` di langkah D dengan URL tersebut.

> Railway menyediakan SSL otomatis (HTTPS) untuk semua domain `*.up.railway.app`.

---

## 🔒 Langkah 4: HTTPS

Redirect HTTP → HTTPS sudah dikonfigurasi di `public/.htaccess`:

```apache
RewriteCond %{HTTP:X-Forwarded-Proto} =http
RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

Railway meneruskan header `X-Forwarded-Proto` sehingga redirect ini bekerja dengan benar di belakang Railway reverse proxy.

---

## 🔑 Environment Variables Lengkap

```env
# ── Production (Railway) ─────────────────────────────────────
# Salin DATABASE_URL dari Variables service Postgres di Railway
DATABASE_URL=postgresql://postgres:PASSWORD@HOST:PORT/railway

APP_ENV=production
APP_URL=https://crud-php-project-production.up.railway.app

# PORT — jangan diisi, Railway inject otomatis
# PORT=

# ── Development (docker-compose.yml) ─────────────────────────
DB_HOST=db
DB_PORT=5432
DB_NAME=crud_db
DB_USER=postgres
DB_PASS=secret
APP_ENV=development
APP_URL=http://localhost:8080
```

---

## 📊 Ringkasan Layer

| Komponen          | Layer     | Provider        | Detail                                     |
|-------------------|-----------|-----------------|---------------------------------------------|
| Docker Engine     | IaaS      | Anda kelola     | Containerisasi aplikasi (lokal & build)     |
| PHP 8.2           | IaaS      | Docker image    | Runtime bahasa                              |
| Apache Web Server | IaaS      | Docker image    | HTTP server, mod_rewrite, headers           |
| PostgreSQL        | PaaS      | Railway         | Database dikelola Railway                   |
| Container Hosting | PaaS      | Railway         | Deploy, scaling, restart otomatis           |
| SSL Certificate   | PaaS      | Railway (auto)  | HTTPS otomatis untuk domain `.up.railway.app` |
| Domain            | PaaS      | Railway         | Subdomain gratis `*.up.railway.app`         |

---

## ✅ Fitur CRUD

| Fitur      | Keterangan                              |
|------------|-----------------------------------------|
| **Create** | Form modal tambah artikel               |
| **Read**   | Grid cards + filter status + search     |
| **Update** | Edit inline via modal                   |
| **Delete** | Konfirmasi modal sebelum hapus          |
| **Stats**  | Total, terbit, draft, total views       |
| **Slug**   | Auto-generate dari judul (unique check) |
| **Views**  | Counter increment saat buka artikel     |

---

## 🐛 Troubleshooting

**Aplikasi tidak bisa konek database**
- Pastikan `DATABASE_URL` sudah diset di Variables service PHP (bukan hanya di service Postgres)
- Cek tab **Deployments** → log deploy untuk pesan error koneksi

**Build gagal karena MPM conflict**
- Sudah ditangani otomatis oleh `docker/entrypoint.sh` yang menonaktifkan `mpm_event` dan mengaktifkan `mpm_prefork`

**Error 404 pada semua route**
- Pastikan `mod_rewrite` aktif dan `AllowOverride All` sudah ada di `docker/apache.conf`
- Cek bahwa `public/.htaccess` ada di repository

**PORT tidak sesuai**
- Jangan set `PORT` secara manual — Railway inject otomatis, dan `entrypoint.sh` sudah membaca nilai `$PORT` untuk mengkonfigurasi Apache