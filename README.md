# 🚀 PHP CRUD CMS — Monolithic Architecture
**Stack:** PHP 8.2 · Apache · PostgreSQL · Docker · Railway (PaaS) · Niagahoster (DNS + SSL)

---

## 🏗️ Arsitektur Sistem

```
┌─────────────────────────────────────────────────────────────┐
│                    ARSITEKTUR MONOLITIK                      │
├──────────────────┬──────────────────────────────────────────┤
│  IaaS Layer      │  Docker container mengelola:             │
│  (Infrastructure)│  - OS (Ubuntu via PHP-Apache image)      │
│                  │  - PHP 8.2 runtime                       │
│                  │  - Apache web server                     │
│                  │  - PostgreSQL database                   │
├──────────────────┼──────────────────────────────────────────┤
│  PaaS Layer      │  Railway mengelola:                      │
│  (Platform)      │  - Container orchestration               │
│                  │  - Auto-deploy dari GitHub               │
│                  │  - Managed PostgreSQL service            │
│                  │  - SSL termination                       │
│                  │  - Environment variables                 │
├──────────────────┼──────────────────────────────────────────┤
│  DNS + SSL       │  Niagahoster:                            │
│                  │  - Domain management                     │
│                  │  - CNAME → Railway URL                   │
│                  │  - SSL via Let's Encrypt (Railway)       │
└──────────────────┴──────────────────────────────────────────┘
```

---

## 📁 Struktur Project

```
crud-php-project/
├── Dockerfile              ← Build image PHP + Apache (IaaS)
├── docker-compose.yml      ← Development lokal
├── railway.toml            ← Konfigurasi Railway (PaaS)
├── .gitignore
├── config/
│   └── database.php        ← Konfigurasi koneksi PostgreSQL
├── src/
│   ├── Database.php        ← Singleton PDO connection
│   └── Content.php         ← Model CRUD
├── public/
│   ├── index.php           ← Entry point: routing + HTML + API
│   └── .htaccess           ← Apache URL rewriting
└── docker/
    ├── apache.conf         ← Virtual host config
    ├── php.ini             ← PHP settings
    ├── entrypoint.sh       ← Railway PORT handling
    └── init.sql            ← Schema + seed data PostgreSQL
```

---

## 🛠️ Langkah 1: Install Docker (IaaS)

### Windows
```bash
# Download Docker Desktop
https://www.docker.com/products/docker-desktop/

# Atau via winget
winget install Docker.DockerDesktop
```

### Ubuntu/Debian
```bash
# Update apt
sudo apt-get update

# Install dependencies
sudo apt-get install -y ca-certificates curl gnupg

# Add Docker GPG key
sudo install -m 0755 -d /etc/apt/keyrings
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | \
  sudo gpg --dearmor -o /etc/apt/keyrings/docker.gpg

# Add Docker repository
echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] \
  https://download.docker.com/linux/ubuntu $(. /etc/os-release && echo "$VERSION_CODENAME") stable" | \
  sudo tee /etc/apt/sources.list.d/docker.list

# Install Docker Engine
sudo apt-get update
sudo apt-get install -y docker-ce docker-ce-cli containerd.io docker-compose-plugin

# Jalankan tanpa sudo
sudo usermod -aG docker $USER
newgrp docker

# Verifikasi
docker --version
docker compose version
```

---

## 🐳 Langkah 2: Jalankan Secara Lokal

```bash
# Clone project
git clone https://github.com/yourusername/crud-php-project.git
cd crud-php-project

# Build & jalankan semua service
docker compose up --build

# Akses di: http://localhost:8080
```

---

## ☁️ Langkah 3: Deploy ke Railway (PaaS)

### A. Setup Railway Project

1. Daftar di [railway.app](https://railway.app)
2. Klik **New Project** → **Deploy from GitHub**
3. Pilih repository `crud-php-project`
4. Railway otomatis deteksi `Dockerfile`

### B. Tambah PostgreSQL Database

Di Railway dashboard:
1. Klik **+ New** → **Database** → **Add PostgreSQL**
2. Railway otomatis inject `DATABASE_URL` ke service PHP Anda

### C. Set Environment Variables

Di Railway → Service → **Variables**:
```
APP_ENV  = production
APP_URL  = https://yourdomain.com
```

> `DATABASE_URL` sudah otomatis tersedia dari PostgreSQL plugin Railway.

### D. Generate Domain Railway

Di Railway → Service → **Settings** → **Domains** → **Generate Domain**

Anda akan mendapat URL seperti: `https://crud-php-project-production.up.railway.app`

---

## 🌐 Langkah 4: Konfigurasi DNS di Niagahoster

### A. Login Niagahoster cPanel

1. Login ke [niagahoster.co.id](https://www.niagahoster.co.id)
2. Masuk ke **cPanel** → **Zone Editor** atau **DNS Management**

### B. Tambah CNAME Record

| Type  | Name    | Value                                        | TTL  |
|-------|---------|----------------------------------------------|------|
| CNAME | `@`     | `crud-php-project-production.up.railway.app` | 3600 |
| CNAME | `www`   | `crud-php-project-production.up.railway.app` | 3600 |

### C. Konfigurasi Custom Domain di Railway

Di Railway → Service → **Settings** → **Domains** → **Custom Domain**:
- Masukkan: `yourdomain.com`
- Railway akan validasi DNS dan auto-issue SSL via **Let's Encrypt**

---

## 🔒 Langkah 5: SSL (HTTPS)

Railway menyediakan SSL **otomatis** via Let's Encrypt setelah custom domain terverifikasi.

Untuk memaksa HTTPS, aktifkan redirect di `public/.htaccess`:
```apache
# Uncomment baris berikut:
RewriteCond %{HTTP:X-Forwarded-Proto} =http
RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

---

## 📊 Ringkasan IaaS vs PaaS

| Komponen         | Layer | Provider        | Detail                          |
|------------------|-------|-----------------|----------------------------------|
| Docker Engine    | IaaS  | Anda kelola     | Containerisasi aplikasi          |
| PHP 8.2          | IaaS  | Docker image    | Runtime bahasa                   |
| Apache Web Server| IaaS  | Docker image    | HTTP server, mod_rewrite, headers|
| PostgreSQL       | IaaS/PaaS | Railway    | Database dikelola Railway        |
| Container Hosting| PaaS  | Railway         | Deploy, scaling, restart otomatis|
| SSL Certificate  | PaaS  | Railway + LE    | Auto-renew Let's Encrypt         |
| DNS Management   | -     | Niagahoster     | CNAME pointing ke Railway        |

---

## 🔑 Environment Variables Lengkap

```env
# Untuk Railway (production)
DATABASE_URL=postgresql://user:pass@host:port/dbname  # otomatis dari Railway
APP_ENV=production
APP_URL=https://yourdomain.com

# Untuk Docker Compose (development)
DB_HOST=db
DB_PORT=5432
DB_NAME=crud_db
DB_USER=postgres
DB_PASS=secret
APP_ENV=development
```

---

## ✅ Fitur CRUD

| Fitur         | Keterangan                            |
|---------------|---------------------------------------|
| **Create**    | Form modal tambah artikel             |
| **Read**      | Grid cards + filter status + search   |
| **Update**    | Edit inline via modal                 |
| **Delete**    | Konfirmasi modal sebelum hapus        |
| **Stats**     | Total, terbit, draft, total views     |
| **Slug**      | Auto-generate dari judul              |
| **Views**     | Counter increment saat buka artikel   |
