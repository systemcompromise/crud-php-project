<?php
// ============================================
// Database Configuration
// PostgreSQL - Railway PaaS
// ============================================

define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ?: '5432');
define('DB_NAME', getenv('DB_NAME') ?: 'crud_db');
define('DB_USER', getenv('DB_USER') ?: 'postgres');
define('DB_PASS', getenv('DB_PASS') ?: 'secret');
define('APP_ENV', getenv('APP_ENV') ?: 'production');
define('APP_URL', getenv('APP_URL') ?: 'http://localhost:8080');

// Railway menyediakan DATABASE_URL dalam format:
// postgresql://user:password@host:port/dbname
if (getenv('DATABASE_URL')) {
    $url = parse_url(getenv('DATABASE_URL'));
    define('DB_DSN', sprintf(
        'pgsql:host=%s;port=%s;dbname=%s',
        $url['host'],
        $url['port'] ?? 5432,
        ltrim($url['path'], '/')
    ));
    define('DB_USERNAME', $url['user']);
    define('DB_PASSWORD', $url['pass']);
} else {
    define('DB_DSN', sprintf('pgsql:host=%s;port=%s;dbname=%s', DB_HOST, DB_PORT, DB_NAME));
    define('DB_USERNAME', DB_USER);
    define('DB_PASSWORD', DB_PASS);
}
