<?php
/**
 * config/connection.php
 * Centralized database connection + global helpers
 * Used by ALL pages (admin, faculty, student, auth)
 */

session_start();

// ===== DATABASE CONFIGURATION =====
$db_host = '127.0.0.1';
$db_name = 'student_clearance';
$db_user = 'root';
$db_pass = '';

// ===== PDO CONNECTION =====
try {
    $pdo = new PDO(
        "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4",
        $db_user,
        $db_pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    // Fail fast, fail loud (but not in production)
    die("Database connection failed: " . $e->getMessage());
}

// ===== TIMEZONE =====
date_default_timezone_set('Asia/Manila');

// ===== CSRF TOKEN =====
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// CSRF hidden input generator
function csrf_input(): string
{
    return '<input type="hidden" name="csrf_token" value="' .
        htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') .
        '">';
}

// CSRF validation helper
function csrf_validate(): bool
{
    return isset($_POST['csrf_token'], $_SESSION['csrf_token']) &&
           hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
}