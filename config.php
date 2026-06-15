<?php
/**
 * Life First — Shared Config
 * Reads credentials from environment. Never hardcode secrets here.
 *
 * On phoenix-ext, set via /etc/lifefirst/lifefirst.env (loaded by Apache SetEnv
 * directives in /etc/apache2/sites-available/lifefirst.conf).
 *
 * For Frank dispatch, the same env vars must be exported before calling PHP.
 */

if (!defined('CLAUDE_API_KEY')) {
    define('CLAUDE_API_KEY', getenv('CLAUDE_API_KEY') ?: '');
}
if (!defined('CLAUDE_MODEL')) {
    define('CLAUDE_MODEL', 'claude-sonnet-4-6');
}

if (!function_exists('getDB')) {
    function getDB() {
        $host = getenv('LF_DB_HOST') ?: 'localhost';
        $user = getenv('LF_DB_USER') ?: 'lifefirst';
        $pass = getenv('LF_DB_PASS') ?: '';
        $name = getenv('LF_DB_NAME') ?: 'lifefirst';
        $conn = new mysqli($host, $user, $pass, $name);
        if ($conn->connect_error) {
            die(json_encode(['status' => 'error', 'message' => 'Database connection failed']));
        }
        $conn->set_charset('utf8mb4');
        return $conn;
    }
}
