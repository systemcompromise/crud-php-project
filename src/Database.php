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
                http_response_code(500);
                die(json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]));
            }
        }
        return self::$instance;
    }
}
