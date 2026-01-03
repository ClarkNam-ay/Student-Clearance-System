<?php
require_once "../config/connection.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'faculty') {
    header("Location: ../login.php");
    exit;
}

if (!csrf_validate()) die("Invalid CSRF");

$id     = $_POST['id'];
$action = $_POST['action'];

if ($action === 'approve') {
    $stmt = $pdo->prepare("
        UPDATE student_clearance_status
        SET status = 'cleared',
            cleared_at = NOW()
        WHERE id = ?
          AND faculty_id = ?
    ");
} else {
    $stmt = $pdo->prepare("
        UPDATE student_clearance_status
        SET status = 'rejected'
        WHERE id = ?
          AND faculty_id = ?
    ");
}

$stmt->execute([$id, $_SESSION['user_id']]);

header("Location: dashboard.php");
exit;