<?php
require_once "../config/connection.php";

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$clearance_id = isset($_GET['clearance_id']) ? (int)$_GET['clearance_id'] : 0;

if (!$clearance_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid clearance ID']);
    exit;
}

$stmt = $pdo->prepare("
    SELECT u.id, u.fullname, u.designation
    FROM clearance_faculty cf
    JOIN users u ON u.id = cf.faculty_id
    WHERE cf.clearance_id = ?
    ORDER BY u.fullname
");
$stmt->execute([$clearance_id]);
$faculty = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'faculty' => $faculty]);