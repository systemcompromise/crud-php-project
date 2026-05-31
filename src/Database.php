<?php
require_once __DIR__ . '/../config/database.php';

class Database {
    private static ?PDO $instance = null;

    public static function getInstance(): PDO {
        if (self::$instance === null) {
            try {
                self::$instance = new PDO(
                    DB_DSN,
                    DB_USERNAME,
                    DB_PASSWORD,
                    [
                        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES   => false,
                    ]
                );
            } catch (PDOException $e) {
                // Log detail error ke server — JANGAN expose ke client
                error_log('[Database] Connection failed: ' . $e->getMessage());

                http_response_code(503);
                header('Content-Type: application/json');
                die(json_encode([
                    'error' => 'Service temporarily unavailable. Please try again later.'
                ]));
            }
        }
        return self::$instance;
    }
}