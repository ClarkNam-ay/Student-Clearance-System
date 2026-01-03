<?php
require_once "../config/connection.php";

/* ===== ADMIN PROTECTION ===== */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

/* ===== FETCH USERS ===== */
$stmt = $pdo->query(
    "SELECT id, fullname, email, role, status, designation
     FROM users
     WHERE role IN ('student','faculty')
     ORDER BY role, fullname"
);
$users = $stmt->fetchAll();

$totalUsers = count($users);
$students = count(array_filter($users, fn($u) => $u['role'] === 'student'));
$faculty = count(array_filter($users, fn($u) => $u['role'] === 'faculty'));
$activeUsers = count(array_filter($users, fn($u) => $u['status'] === 'active'));
?>
<?php include "header.php"; ?>
<?php include "sidebar.php"; ?>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
.main-content {
    margin-left: 260px;
    padding: 30px;
    min-height: 100vh;
    background: #f8fafc;
}

.page-header {
    background: white;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    margin-bottom: 30px;
}

.page-header h1 {
    font-family: 'Inter', sans-serif;
    font-size: 28px;
    color: #1e293b;
    font-weight: 700;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.page-header p {
    color: #64748b;
    font-size: 14px;
    font-family: 'Inter', sans-serif;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    padding: 24px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    display: flex;
    align-items: center;
    gap: 16px;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
}

.stat-icon {
    width: 56px;
    height: 56px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: white;
}

.stat-icon.students {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.stat-icon.faculty {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.stat-icon.active {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}

.stat-content h3 {
    font-family: 'Inter', sans-serif;
    font-size: 32px;
    color: #1e293b;
    font-weight: 700;
    line-height: 1;
}

.stat-content p {
    font-family: 'Inter', sans-serif;
    font-size: 13px;
    color: #64748b;
    margin-top: 4px;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}



@keyframes pulse {

    0%,
    100% {
        opacity: 1;
    }

    50% {
        opacity: 0.5;
    }
}


@media (max-width: 1024px) {
    .main-content {
        margin-left: 0;
        padding: 20px;
    }
}

@media (max-width: 768px) {
    .main-content {
        padding: 15px;
    }

    .page-header {
        padding: 20px;
    }

    .page-header h1 {
        font-size: 22px;
    }

    .stats-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="main-content">
    <div class="page-header">
        <h1><i class="fas fa-users-cog"></i>Welcome to Admin Dashboard</h1>
        <p>Manage your institution's users and settings</p>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon students">
                <i class="fas fa-user-graduate"></i>
            </div>
            <div class="stat-content">
                <h3><?= $students ?></h3>
                <p>Total Students</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon faculty">
                <i class="fas fa-chalkboard-teacher"></i>
            </div>
            <div class="stat-content">
                <h3><?= $faculty ?></h3>
                <p>Total Faculty</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon active">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-content">
                <h3><?= $activeUsers ?></h3>
                <p>Active Users</p>
            </div>
        </div>
    </div>


</div>

<?php include "footer.php"; ?>