<?php
require_once "../config/connection.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_validate()) {
    die("Invalid request");
}

$student_id   = $_SESSION['user_id'];
$clearance_id = $_POST['clearance_id'];
$faculty_id   = $_POST['faculty_id'];

$stmt = $pdo->prepare("
    UPDATE student_clearance_status
    SET status = 'requested',
        requested_at = NOW()
    WHERE student_id = ?
      AND clearance_id = ?
      AND faculty_id = ?
      AND status = 'pending'
");

$stmt->execute([$student_id, $clearance_id, $faculty_id]);

header("Location: dashboard.php");
exit;