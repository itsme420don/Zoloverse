<?php
/**
 * db/seed.php — Apply the PostgreSQL schema and seed data to the connected database.
 * Run from the Render Shell (or any CLI with the DB_* env vars set):
 *     php db/seed.php
 * Safe to re-run (idempotent). Exits 1 on the first failing statement.
 */

require_once __DIR__ . '/../includes/init.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only.');
}

$sqlFile = __DIR__ . '/setup.postgres.sql';
$sql = file_get_contents($sqlFile);
if ($sql === false) {
    fwrite(STDERR, "Cannot read $sqlFile\n");
    exit(1);
}

$statements = preg_split('/;\r?\n/', $sql);
$ran = 0;
foreach ($statements as $stmt) {
    $stmt = trim($stmt);
    while (preg_match('/^--[^\r\n]*(?:\r?\n|$)/', $stmt)) {
        $stmt = trim(preg_replace('/^--[^\r\n]*(?:\r?\n|$)/', '', $stmt));
    }
    if ($stmt === '') {
        continue;
    }

    try {
        db()->exec($stmt . ';');
        $ran++;
        echo "OK: " . substr(str_replace("\n", ' ', trim($stmt, "; \t\r\n")), 0, 70) . "\n";
    } catch (PDOException $e) {
        fwrite(STDERR, "FAIL: " . substr(str_replace("\n", ' ', trim($stmt, "; \t\r\n")), 0, 70) . "\n  -> " . $e->getMessage() . "\n");
        exit(1);
    }
}

echo "Database ready: $ran statement(s) applied.\n";
