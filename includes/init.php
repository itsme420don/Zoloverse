<?php
/**
 * includes/init.php — Shared bootstrap for all Zoloverse API endpoints.
 *
 * Usage from any endpoint file at project root:
 *     require_once 'includes/init.php';
 *
 * This loads the database layer (CORS headers, PDO, JSON helpers)
 * and the authentication helpers (session tokens, guards).
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth_helpers.php';
