<?php
/**
 * includes/db.php — Centralized database connection & JSON API helpers.
 * Every API endpoint boots through includes/init.php, which loads this file.
 */

require_once __DIR__ . '/../config.php';

/* ---------------- CORS & JSON API Headers ---------------- */
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Authorization, X-Requested-With');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

/* ---------------- Database Connection (singleton) ---------------- */
function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $port = DB_PORT ?: (DB_DRIVER === 'pgsql' ? '5432' : '3306');

        if (DB_DRIVER === 'pgsql') {
            $dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s;sslmode=require', DB_HOST, $port, DB_NAME);
        } else {
            $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', DB_HOST, $port, DB_NAME);
        }

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            error_log('[zoloverse] DB connection failed: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Database unavailable. Please try again later.',
            ]);
            exit;
        }
    }

    return $pdo;
}

/* Backward-compatible global alias so existing code can keep using $pdo. */
$pdo = db();

/* ---------------- JSON Response Helpers ---------------- */
function json_response($data, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($data);
    exit;
}

function json_success(string $message, array $extra = []): void
{
    json_response(array_merge(['success' => true, 'message' => $message], $extra));
}

function json_error(string $message, int $code = 400): void
{
    json_response(['success' => false, 'message' => $message], $code);
}

/** Decode and return the JSON request body as an object (null when invalid). */
function read_json_body(): ?object
{
    return json_decode(file_get_contents('php://input'));
}

/** Verify that every field in $fields is a non-empty value on $data. */
function require_fields(?object $data, array $fields): bool
{
    if (!$data) {
        return false;
    }

    foreach ($fields as $field) {
        $value = $data->{$field} ?? '';
        if (is_string($value) ? trim($value) === '' : $value === '') {
            return false;
        }
    }
    return true;
}
