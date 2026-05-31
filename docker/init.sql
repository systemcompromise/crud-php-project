-- ============================================
-- Database Initialization Script
-- PostgreSQL on Railway (PaaS)
-- ============================================

CREATE TABLE IF NOT EXISTS contents (
    id          SERIAL PRIMARY KEY,
    title       VARCHAR(255) NOT NULL,
    slug        VARCHAR(255) UNIQUE NOT NULL,
    body        TEXT NOT NULL,
    category    VARCHAR(100) DEFAULT 'Umum',
    status      VARCHAR(20) DEFAULT 'draft' CHECK (status IN ('draft', 'published', 'archived')),
    author      VARCHAR(100) NOT NULL,
    views       INTEGER DEFAULT 0,
    created_at  TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at  TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Auto-update updated_at trigger
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = NOW();
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER update_contents_updated_at
    BEFORE UPDATE ON contents
    FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();

-- Index for performance
CREATE INDEX IF NOT EXISTS idx_contents_status ON contents(status);
CREATE INDEX IF NOT EXISTS idx_contents_slug ON contents(slug);
CREATE INDEX IF NOT EXISTS idx_contents_created_at ON contents(created_at DESC);

-- Seed data
INSERT INTO contents (title, slug, body, category, status, author) VALUES
(
    'Selamat Datang di Platform Kami',
    'selamat-datang',
    'Ini adalah artikel pertama di platform manajemen konten kami. Platform ini dibangun menggunakan PHP 8.2, Apache, PostgreSQL, dan di-deploy menggunakan Docker di Railway dengan domain dari Niagahoster.',
    'Pengumuman',
    'published',
    'Admin'
),
(
    'Panduan Penggunaan CMS',
    'panduan-cms',
    'Anda dapat membuat, mengedit, dan menghapus konten melalui panel administrasi. Gunakan tombol Tambah Konten untuk membuat artikel baru. Setiap artikel dapat diberi status Draft, Published, atau Archived.',
    'Tutorial',
    'published',
    'Admin'
),
(
    'Arsitektur Sistem Cloud',
    'arsitektur-cloud',
    'Sistem ini menggunakan arsitektur monolitik yang di-deploy di Railway sebagai PaaS. Database PostgreSQL dikelola sebagai layanan terpisah. DNS dikonfigurasi melalui Niagahoster dengan SSL otomatis dari Let''s Encrypt.',
    'Teknologi',
    'draft',
    'Developer'
);
