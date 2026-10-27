<?php
/**
 * config.php — Zoloverse application settings.
 * Every value can be overridden with an environment variable (used on Render).
 * Local XAMPP keeps working unchanged because the defaults match.
 */

declare(strict_types=1);

/* ---------------- Database Credentials ---------------- */
define('DB_DRIVER', getenv('DB_DRIVER') ?: 'mysql');   // 'mysql' (local) or 'pgsql' (Render)
define('DB_HOST',   getenv('DB_HOST')   ?: 'localhost');
define('DB_PORT',   getenv('DB_PORT')   ?: '');        // defaults: 3306 mysql, 5432 pgsql
define('DB_NAME',   getenv('DB_NAME')   ?: 'zoloverse');
define('DB_USER',   getenv('DB_USER')   ?: 'root');
define('DB_PASS',   getenv('DB_PASS')   ?: '');

/* ---------------- Google OAuth (Social Login) ---------------- */
// Paste your Google API Console Client ID here (or set GOOGLE_CLIENT_ID env var).
define('GOOGLE_CLIENT_ID', getenv('GOOGLE_CLIENT_ID') ?: 'YOUR_GOOGLE_CLIENT_ID_HERE.apps.googleusercontent.com');

/* ---------------- Session & Security ---------------- */
// Cryptographic secret used to sign session tokens.
define('JWT_SECRET', getenv('JWT_SECRET') ?: '47b853ea63cd16fca969c3a3734a781b0a82772591e01869e5d7ad833df867f1');
define('SESSION_TTL', (int) (getenv('SESSION_TTL') ?: 86400)); // Session lifetime in seconds (24 hours)

/* ---------------- Notifications ---------------- */
define('ORDER_NOTIFY_EMAIL', getenv('ORDER_NOTIFY_EMAIL') ?: 'zoloverse26@gmail.com');
