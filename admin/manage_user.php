<?php
require_once "../config/connection.php";

/* ===== ADMIN PROTECTION ===== */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$message = "";
$error = "";

/* ===== CREATE USER ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_user'])) {
    
    if (!csrf_validate()) {
        die("Invalid CSRF token.");
    }

    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role'];

    $course = $_POST['course'] ?? null;
    $year_level = $_POST['year_level'] ?? null;
    $section = $_POST['section'] ?? null;
    $designation = trim($_POST['designation'] ?? null);

    if ($fullname === "" || $email === "" || $password === "" || $role === "") {
    $error = "All required fields must be filled.";
    } 
    elseif ($role === 'student' && ($course === "" || $year_level === "" || $section === "")) {
        $error = "Student fields are incomplete.";
    }
    elseif ($role === 'faculty' && $designation === "") {
    $error = "Faculty designation is required.";
    }
    elseif (!in_array($role, ['student', 'faculty'])) {
        $error = "Invalid role selected.";
    }
    else {
        $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$email]);

        if ($check->fetch()) {
            $error = "Email already exists.";
        }
        else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("
            INSERT INTO users
            (fullname, course, year_level, section, designation, email, password, role)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");

            if ($stmt->execute([$fullname, $course, $year_level, $section, $designation, $email, $hashedPassword, $role])) {
                $message = ucfirst($role) . " account created successfully.";
            } else {
                $error = "Failed to create account.";
            }
        }
    }
}

/* ===== ACTIVATE / DEACTIVATE ===== */
if (isset($_GET['toggle'], $_GET['id'])) {
    $id = (int)$_GET['id'];

    $stmt = $pdo->prepare(
        "UPDATE users
         SET status = IF(status='active','inactive','active')
         WHERE id = ? AND role IN ('student','faculty')"
    );
    $stmt->execute([$id]);

    header("Location: manage_users.php");
    exit;
}

/* ===== UPDATE DESIGNATION ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_designation'])) {

    if (!csrf_validate()) {
        die("Invalid CSRF token.");
    }

    $id = (int)$_POST['user_id'];
    $designation = trim($_POST['designation']);

    $stmt = $pdo->prepare(
        "UPDATE users
         SET designation = ?
         WHERE id = ? AND role = 'faculty'"
    );
    $stmt->execute([$designation, $id]);

    $message = "Faculty designation updated.";
}

/* ===== UPDATE USER ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_user'])) {

    if (!csrf_validate()) die("Invalid CSRF token.");

    $id       = (int) $_POST['user_id'];
    $fullname = trim($_POST['fullname']);
    $email    = trim($_POST['email']);
    $role     = $_POST['role'];

    if ($role === 'student') {
        $course     = trim($_POST['course']);
        $year_level = trim($_POST['year_level']);
        $section    = trim($_POST['section']);

        $stmt = $pdo->prepare("
            UPDATE users
            SET fullname=?, email=?, role=?, course=?, year_level=?, section=?
            WHERE id=? AND role IN ('student','faculty')
        ");

        $stmt->execute([$fullname, $email, $role, $course, $year_level, $section, $id]);

    } else {
        $stmt = $pdo->prepare("
            UPDATE users
            SET fullname=?, email=?, role=?
            WHERE id=? AND role='faculty'
        ");

        $stmt->execute([$fullname, $email, $role, $id]);
    }

    $message = "User updated successfully.";
}

/* ===== DELETE USER ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {

    if (!csrf_validate()) {
        die("Invalid CSRF token.");
    }

    $id = (int) $_POST['user_id'];

    $stmt = $pdo->prepare(
        "DELETE FROM users 
         WHERE id = ? AND role IN ('student','faculty')"
    );

    if ($stmt->execute([$id])) {
        $message = "User account deleted successfully.";
    } else {
        $error = "Failed to delete user.";
    }
}



/* ===== FETCH USERS ===== */
$stmt = $pdo->query(
    "SELECT id, fullname, email, role, course, year_level, section, status, designation
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
    margin-top: 70px;
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
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.page-header-content h1 {
    font-family: 'Inter', sans-serif;
    font-size: 28px;
    color: #1e293b;
    font-weight: 700;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.page-header-content p {
    color: #64748b;
    font-size: 14px;
    font-family: 'Inter', sans-serif;
}

.btn-create {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 12px 24px;
    border-radius: 10px;
    border: none;
    font-family: 'Inter', sans-serif;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.btn-create:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
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

.alert-success {
    background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
    color: #065f46;
    padding: 16px 24px;
    border-radius: 12px;
    margin-bottom: 30px;
    display: flex;
    align-items: center;
    gap: 12px;
    font-weight: 500;
    font-family: 'Inter', sans-serif;
    box-shadow: 0 2px 8px rgba(16, 185, 129, 0.2);
    animation: slideDown 0.4s ease;
}

.alert-error {
    background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
    color: #991b1b;
    padding: 16px 24px;
    border-radius: 12px;
    margin-bottom: 30px;
    display: flex;
    align-items: center;
    gap: 12px;
    font-weight: 500;
    font-family: 'Inter', sans-serif;
    box-shadow: 0 2px 8px rgba(239, 68, 68, 0.2);
    animation: slideDown 0.4s ease;
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

.table-container {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    overflow: hidden;
}

.table-header {
    padding: 24px 30px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.table-header h2 {
    font-family: 'Inter', sans-serif;
    font-size: 18px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 0;
}

.table-wrapper {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
}

thead {
    background: #f8fafc;
    border-bottom: 2px solid #e2e8f0;
}

th {
    padding: 16px 20px;
    text-align: left;
    font-family: 'Inter', sans-serif;
    font-size: 12px;
    font-weight: 600;
    color: #475569;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

td {
    padding: 18px 20px;
    border-bottom: 1px solid #f1f5f9;
    color: #334155;
    font-family: 'Inter', sans-serif;
    font-size: 14px;
}

tbody tr {
    transition: background-color 0.2s ease;
}

tbody tr:hover {
    background-color: #f8fafc;
}

.user-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
    font-size: 16px;
    font-family: 'Inter', sans-serif;
    flex-shrink: 0;
}

.user-details h4 {
    font-family: 'Inter', sans-serif;
    font-size: 14px;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 2px;
}

.user-details p {
    font-family: 'Inter', sans-serif;
    font-size: 12px;
    color: #64748b;
}

.role-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: 20px;
    font-family: 'Inter', sans-serif;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.role-badge.student {
    background: #dbeafe;
    color: #1e40af;
}

.role-badge.faculty {
    background: #fce7f3;
    color: #be185d;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    border-radius: 20px;
    font-family: 'Inter', sans-serif;
    font-size: 11px;
    font-weight: 600;
}

.status-badge.active {
    background: #d1fae5;
    color: #065f46;
}

.status-badge.inactive {
    background: #fee2e2;
    color: #991b1b;
}

.status-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    animation: pulse 2s infinite;
}

.status-badge.active .status-dot {
    background: #10b981;
}

.status-badge.inactive .status-dot {
    background: #ef4444;
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

.designation-form {
    display: flex;
    gap: 8px;
    align-items: center;
}

.designation-form input[type="text"] {
    padding: 8px 12px;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    font-family: 'Inter', sans-serif;
    font-size: 13px;
    width: 160px;
    transition: all 0.2s ease;
}

.designation-form input[type="text"]:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.btn {
    padding: 8px 14px;
    border: none;
    border-radius: 8px;
    font-family: 'Inter', sans-serif;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.btn-toggle {
    background: #3b82f6;
    color: white;
}

.btn-toggle:hover {
    background: #2563eb;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #64748b;
}

.empty-state i {
    font-size: 64px;
    color: #cbd5e1;
    margin-bottom: 20px;
}

.empty-state h3 {
    font-family: 'Inter', sans-serif;
    font-size: 20px;
    color: #475569;
    margin-bottom: 8px;
}

.empty-state p {
    font-family: 'Inter', sans-serif;
    font-size: 14px;
}

/* Modal Styles */
.modal-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.6);
    z-index: 1000;
    animation: fadeIn 0.3s ease;
}

.modal-overlay.active {
    display: flex;
    align-items: center;
    justify-content: center;
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }

    to {
        opacity: 1;
    }
}

.modal {
    background: white;
    border-radius: 16px;
    width: 90%;
    max-width: 500px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    animation: slideUp 0.3s ease;
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.modal-header {
    padding: 24px 30px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-radius: 16px 16px 0 0;
}

.modal-header h2 {
    font-family: 'Inter', sans-serif;
    font-size: 22px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 0;
}

.modal-close {
    background: rgba(255, 255, 255, 0.2);
    border: none;
    width: 32px;
    height: 32px;
    border-radius: 8px;
    color: white;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
}

.modal-close:hover {
    background: rgba(255, 255, 255, 0.3);
}

.modal-body {
    padding: 30px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    font-family: 'Inter', sans-serif;
    font-size: 14px;
    font-weight: 600;
    color: #334155;
    margin-bottom: 8px;
}

.form-group label i {
    color: #667eea;
    margin-right: 6px;
}

.form-group input,
.form-group select {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    font-family: 'Inter', sans-serif;
    font-size: 14px;
    transition: all 0.2s ease;
}

.form-group input:focus,
.form-group select:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.form-group select {
    cursor: pointer;
}

.modal-footer {
    padding: 20px 30px;
    background: #f8fafc;
    border-radius: 0 0 16px 16px;
    display: flex;
    gap: 12px;
    justify-content: flex-end;
}

.btn-cancel {
    background: #e2e8f0;
    color: #475569;
    padding: 12px 24px;
    border: none;
    border-radius: 10px;
    font-family: 'Inter', sans-serif;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-cancel:hover {
    background: #cbd5e1;
}

.btn-submit {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 12px 24px;
    border: none;
    border-radius: 10px;
    font-family: 'Inter', sans-serif;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    gap: 8px;
}

.btn-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
}

@media (max-width: 1024px) {
    .main-content {
        margin-left: 0;
        padding: 20px;
    }

    .page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 20px;
    }
}

@media (max-width: 768px) {
    .main-content {
        padding: 15px;
    }

    .page-header {
        padding: 20px;
    }

    .page-header-content h1 {
        font-size: 22px;
    }

    .stats-grid {
        grid-template-columns: 1fr;
    }

    th,
    td {
        padding: 12px;
        font-size: 12px;
    }

    .user-avatar {
        width: 32px;
        height: 32px;
        font-size: 14px;
    }

    .designation-form {
        flex-direction: column;
        align-items: stretch;
    }

    .designation-form input[type="text"] {
        width: 100%;
    }

    .modal {
        width: 95%;
    }

    .modal-body {
        padding: 20px;
    }
}
</style>

<div class="main-content">
    <div class="page-header">
        <div class="page-header-content">
            <h1><i class="fas fa-users-cog"></i> User Management</h1>
            <p>Manage students and faculty accounts, update designations, and control access</p>
        </div>
        <button class="btn-create" onclick="openModal()">
            <i class="fas fa-user-plus"></i>
            Create New User
        </button>
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

    <?php if ($error): ?>
    <script>
    // Auto-open modal if there's an error
    openModal();
    <?php endif; ?>
    </script>

    <?php if ($message): ?>
    <div class="alert-success">
        <i class="fas fa-check-circle"></i>
        <span><?= htmlspecialchars($message) ?></span>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
    <div class="alert-error">
        <i class="fas fa-exclamation-circle"></i>
        <span><?= htmlspecialchars($error) ?></span>
    </div>
    <?php endif; ?>

    <div class="table-container">
        <div class="table-header">
            <h2><i class="fas fa-table"></i> User Directory</h2>
        </div>

        <?php if (empty($users)): ?>
        <div class="empty-state">
            <i class="fas fa-users-slash"></i>
            <h3>No Users Found</h3>
            <p>There are no students or faculty registered in the system.</p>
        </div>
        <?php else: ?>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Role</th>
                        <th>Designation</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                    <tr>
                        <td>
                            <div class="user-info">
                                <div class="user-avatar">
                                    <?= strtoupper(substr($u['fullname'], 0, 1)) ?>
                                </div>
                                <div class="user-details">
                                    <h4><?= htmlspecialchars($u['fullname']) ?></h4>
                                    <p><?= htmlspecialchars($u['email']) ?></p>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="role-badge <?= $u['role'] ?>">
                                <i
                                    class="fas fa-<?= $u['role'] === 'student' ? 'user-graduate' : 'chalkboard-teacher' ?>"></i>
                                <?= ucfirst($u['role']) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($u['role'] === 'faculty'): ?>
                            <form method="POST" class="designation-form">
                                <?= csrf_input() ?>
                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                <input type="text" name="designation"
                                    value="<?= htmlspecialchars($u['designation'] ?? '') ?>"
                                    placeholder="e.g. Department Head">
                                <button type="submit" name="update_designation" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Save
                                </button>
                            </form>
                            <?php else: ?>
                            <span style="color: #94a3b8;">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="status-badge <?= $u['status'] ?>">
                                <span class="status-dot"></span>
                                <?= ucfirst($u['status']) ?>
                            </span>
                        </td>
                        <td>
                            <a class="btn btn-toggle" href="manage_users.php?toggle=1&id=<?= $u['id'] ?>"
                                onclick="return confirm('Are you sure you want to change this user\'s status?')">
                                <i class="fas fa-sync-alt"></i> Toggle
                            </a>
                        </td>

                        <td>
                            <button class="btn btn-primary" onclick="openEditModal(
    <?= $u['id'] ?>,
    '<?= htmlspecialchars($u['fullname'], ENT_QUOTES) ?>',
    '<?= $u['course'] ?? '' ?>',
    '<?= $u['year_level'] ?? '' ?>',
    '<?= $u['section'] ?? '' ?>',
    '<?= htmlspecialchars($u['email'], ENT_QUOTES) ?>',
    '<?= $u['role'] ?>'
)">
                                <i class="fas fa-edit"></i> Edit
                            </button>


                            <button class="btn btn-toggle" onclick="openDeleteModal(<?= $u['id'] ?>)">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </td>

                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Create User Modal -->
<div class="modal-overlay" id="createUserModal">
    <div class="modal">
        <div class="modal-header">
            <h2><i class="fas fa-user-plus"></i> Create New User</h2>
            <button class="modal-close" onclick="closeModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <?= csrf_input() ?>

                <div class="form-group">
                    <label><i class="fas fa-user"></i> Full Name</label>
                    <input type="text" name="fullname" required>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-user-tag"></i> Role</label>
                    <select name="role" id="create_role" required onchange="toggleCreateRoleFields()">
                        <option value="student" selected>Student</option>
                        <option value="faculty">Faculty</option>
                    </select>
                </div>

                <!-- STUDENT FIELDS -->
                <div id="studentCreateFields">
                    <div class="form-group">
                        <label><i class="fas fa-graduation-cap"></i> Course</label>
                        <select name="course">
                            <option value="BSCS">BS in Computer Science</option>
                            <option value="BSIS">BS in Information Systems</option>
                            <option value="BSIT">BS in Information Technology</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-layer-group"></i> Year Level</label>
                        <select name="year_level">
                            <option value="1">1st Year</option>
                            <option value="2">2nd Year</option>
                            <option value="3">3rd Year</option>
                            <option value="4">4th Year</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-chalkboard"></i> Section</label>
                        <select name="section">
                            <option value="A">Section A</option>
                            <option value="B">Section B</option>
                            <option value="C">Section C</option>
                            <option value="D">Section D</option>
                        </select>
                    </div>
                </div>

                <!-- FACULTY FIELDS -->
                <div id="facultyCreateFields" style="display:none;">
                    <div class="form-group">
                        <label><i class="fas fa-id-badge"></i> Designation</label>
                        <input type="text" name="designation" placeholder="e.g. Department Head">
                    </div>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-envelope"></i> Email</label>
                    <input type="email" name="email" required>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-lock"></i> Password</label>
                    <input type="password" name="password" required>
                </div>
            </div>


            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal()">
                    Cancel
                </button>
                <button type="submit" name="create_user" class="btn-submit">
                    <i class="fas fa-check"></i>
                    Create User
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal-overlay" id="editUserModal">
    <div class="modal">
        <div class="modal-header">
            <h2><i class="fas fa-user-edit"></i> Edit User</h2>
            <button class="modal-close" onclick="closeEditModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form method="POST">
            <div class="modal-body">
                <?= csrf_input() ?>
                <input type="hidden" name="user_id" id="edit_user_id">

                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="fullname" id="edit_fullname" required>
                </div>

                <div id="studentFields">
                    <div class="form-group">
                        <label>Course</label>
                        <select name="course" id="edit_course" required>
                            <option value="BSCS">BS in Computer Science</option>
                            <option value="BSIS">BS in Information Systems</option>
                            <option value="BSIT">BS in Information Technology</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Year Level</label>
                        <select name="year_level" id="edit_year_level" required>
                            <option value="1">1st Year</option>
                            <option value="2">2nd Year</option>
                            <option value="3">3rd Year</option>
                            <option value="4">4th Year</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Section</label>
                        <select name="section" id="edit_section" required>
                            <option value="A">Section A</option>
                            <option value="B">Section B</option>
                            <option value="C">Section C</option>
                            <option value="D">Section D</option>
                        </select>
                    </div>
                </div>

                <div class=" form-group">
                    <label>Email</label>
                    <input type="email" name="email" id="edit_email" required>
                </div>

                <div class="form-group">
                    <label>Role</label>
                    <select name="role" id="edit_role" required>
                        <option value="student">Student</option>
                        <option value="faculty">Faculty</option>
                    </select>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeEditModal()">Cancel</button>
                <button type="submit" name="edit_user" class="btn-submit">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Delete User Modal -->
<div class="modal-overlay" id="deleteUserModal">
    <div class="modal">
        <div class="modal-header">
            <h2><i class="fas fa-trash"></i> Delete User</h2>
            <button class="modal-close" onclick="closeDeleteModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form method="POST">
            <div class="modal-body">
                <?= csrf_input() ?>
                <input type="hidden" name="user_id" id="delete_user_id">
                <p style="font-size:14px;color:#475569;">
                    Are you sure you want to permanently delete this user?
                    <br><strong>This action cannot be undone.</strong>
                </p>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeDeleteModal()">Cancel</button>
                <button type="submit" name="delete_user" class="btn-submit" style="background:#ef4444;">
                    <i class="fas fa-trash"></i> Delete
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal() {
    document.getElementById('createUserModal').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeModal() {
    document.getElementById('createUserModal').classList.remove('active');
    document.body.style.overflow = 'auto';
}

// Close modal when clicking outside
document.getElementById('createUserModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeModal();
    }
});

//Modal for edit and delete
function openEditModal(id, fullname, course, year_level, section, email, role) {
    document.getElementById('edit_user_id').value = id;
    document.getElementById('edit_fullname').value = fullname;
    document.getElementById('edit_email').value = email;
    document.getElementById('edit_role').value = role;

    const studentFields = document.getElementById('studentFields');

    if (role === 'student') {
        studentFields.style.display = 'block';
        document.getElementById('edit_course').value = course;
        document.getElementById('edit_year_level').value = year_level;
        document.getElementById('edit_section').value = section;
    } else {
        studentFields.style.display = 'none';
    }

    document.getElementById('editUserModal').classList.add('active');
}


function closeEditModal() {
    document.getElementById('editUserModal').classList.remove('active');
}

function openDeleteModal(id) {
    document.getElementById('delete_user_id').value = id;
    document.getElementById('deleteUserModal').classList.add('active');
}

function closeDeleteModal() {
    document.getElementById('deleteUserModal').classList.remove('active');
}

function toggleCreateRoleFields() {
    const role = document.getElementById('create_role').value;
    const studentFields = document.getElementById('studentCreateFields');
    const facultyFields = document.getElementById('facultyCreateFields');

    if (role === 'faculty') {
        studentFields.style.display = 'none';
        facultyFields.style.display = 'block';
    } else {
        studentFields.style.display = 'block';
        facultyFields.style.display = 'none';
    }
}

<?php if ($error): ?>
openModal();
<?php endif; ?>
</script>
<?php include "footer.php"; ?>