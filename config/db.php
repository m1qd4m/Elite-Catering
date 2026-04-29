<?php
// ============================================================
// config/db.php — Database Connection + Helpers
// ============================================================

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'catering_db');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die('
    <div style="font-family:sans-serif;padding:2rem;background:#fee2e2;color:#991b1b;border-radius:8px;margin:2rem;border:1px solid #fca5a5">
        <strong>⚠ Database Connection Failed</strong><br>
        ' . htmlspecialchars($conn->connect_error) . '<br><br>
        <small>Make sure XAMPP MySQL is running and you have imported <code>database/catering.sql</code> into phpMyAdmin.</small>
    </div>');
}

$conn->set_charset('utf8mb4');

// ── Safe display escaping (use on OUTPUT only) ─────────────
function e($str) {
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

// ── Sanitize for use in direct SQL strings (LIKE clauses etc) 
// Never use for INSERT/UPDATE — use prepared statements instead
function sanitize($conn, $value) {
    return $conn->real_escape_string(trim((string)$value));
}

// ── Redirect helper ────────────────────────────────────────
function redirect($url) {
    if (!headers_sent()) {
        header("Location: $url");
    } else {
        echo '<script>window.location="' . addslashes($url) . '";</script>';
    }
    exit;
}

// ── Flash messages ─────────────────────────────────────────
function setFlash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

// ── Get first name safely from session ────────────────────
function getFirstName() {
    $name = $_SESSION['name'] ?? 'User';
    $parts = explode(' ', trim($name));
    return $parts[0] ?? 'User';
}
