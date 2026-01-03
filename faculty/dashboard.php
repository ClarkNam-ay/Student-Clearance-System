<?php
require_once "../config/connection.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'faculty') {
    header("Location: ../login.php");
    exit;
}

$faculty_id = $_SESSION['user_id'];
$message = "";
$error = "";

// Handle approve/reject actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_request'])) {
    
    if (!csrf_validate()) {
        die("Invalid CSRF token");
    }

    $request_id = (int)$_POST['request_id'];
    $action = $_POST['action'];

    // Verify this request belongs to this faculty
    $verify = $pdo->prepare("
        SELECT student_id, clearance_id, status 
        FROM student_clearance_status 
        WHERE id = ? AND faculty_id = ?
    ");
    $verify->execute([$request_id, $faculty_id]);
    $request = $verify->fetch();

    if ($request && $request['status'] === 'requested') {
        if ($action === 'approve') {
            $stmt = $pdo->prepare("
                UPDATE student_clearance_status
                SET status = 'cleared',
                    cleared_at = NOW()
                WHERE id = ? AND faculty_id = ?
            ");
            
            if ($stmt->execute([$request_id, $faculty_id])) {
                $message = "Clearance request approved successfully!";
            } else {
                $error = "Failed to approve request. Please try again.";
            }
        } elseif ($action === 'reject') {
            $stmt = $pdo->prepare("
                UPDATE student_clearance_status
                SET status = 'pending',
                    requested_at = NULL
                WHERE id = ? AND faculty_id = ?
            ");
            
            if ($stmt->execute([$request_id, $faculty_id])) {
                $message = "Clearance request rejected.";
            } else {
                $error = "Failed to reject request. Please try again.";
            }
        }
    } else {
        $error = "Invalid request or already processed.";
    }
}

// Get pending requests
$stmt = $pdo->prepare("
    SELECT
        sc.id,
        sc.student_id,
        sc.clearance_id,
        sc.requested_at,
        u.fullname AS student_name,
        u.email AS student_email,
        c.title AS clearance_title,
        c.description AS clearance_description
    FROM student_clearance_status sc
    JOIN users u ON u.id = sc.student_id
    JOIN clearances c ON c.id = sc.clearance_id
    WHERE sc.faculty_id = ?
    AND sc.status = 'requested'
    ORDER BY sc.requested_at ASC
");
$stmt->execute([$faculty_id]);
$requests = $stmt->fetchAll();

// Get statistics
$stats = $pdo->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'cleared' THEN 1 ELSE 0 END) as cleared,
        SUM(CASE WHEN status = 'requested' THEN 1 ELSE 0 END) as requested,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending
    FROM student_clearance_status
    WHERE faculty_id = ?
");
$stats->execute([$faculty_id]);
$statistics = $stats->fetch();

$totalCleared = $statistics['cleared'] ?? 0;
$totalRequested = $statistics['requested'] ?? 0;
$totalPending = $statistics['pending'] ?? 0;
$totalAssigned = $statistics['total'] ?? 0;
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

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
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

.stat-icon.total {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.stat-icon.cleared {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

.stat-icon.requested {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
}

.stat-icon.pending {
    background: linear-gradient(135deg, #94a3b8 0%, #64748b 100%);
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

.section-header {
    background: white;
    padding: 20px 30px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    margin-bottom: 20px;
}

.section-header h2 {
    font-family: 'Inter', sans-serif;
    font-size: 20px;
    font-weight: 600;
    color: #1e293b;
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 0;
}

.request-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    overflow: hidden;
    margin-bottom: 20px;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.request-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
}

.request-header {
    padding: 20px 24px;
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.request-student {
    display: flex;
    align-items: center;
    gap: 12px;
}

.student-avatar {
    width: 48px;
    height: 48px;
    border-radius: 10px;
    background: rgba(255, 255, 255, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
    font-size: 18px;
    font-family: 'Inter', sans-serif;
}

.student-info h3 {
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 2px;
}

.student-info p {
    font-size: 13px;
    opacity: 0.9;
}

.request-time {
    text-align: right;
    font-size: 13px;
    opacity: 0.9;
}

.request-time i {
    margin-right: 4px;
}

.request-body {
    padding: 24px;
}

.clearance-info {
    background: #f8fafc;
    padding: 16px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.clearance-info h4 {
    font-family: 'Inter', sans-serif;
    font-size: 15px;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.clearance-info p {
    font-family: 'Inter', sans-serif;
    font-size: 13px;
    color: #64748b;
    line-height: 1.5;
}

.request-actions {
    display: flex;
    gap: 12px;
}

.btn-approve {
    flex: 1;
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    padding: 12px 24px;
    border: none;
    border-radius: 10px;
    font-family: 'Inter', sans-serif;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

.btn-approve:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
}

.btn-reject {
    flex: 1;
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: white;
    padding: 12px 24px;
    border: none;
    border-radius: 10px;
    font-family: 'Inter', sans-serif;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
}

.btn-reject:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(239, 68, 68, 0.4);
}

.empty-state {
    text-align: center;
    padding: 80px 20px;
    color: #64748b;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.empty-state i {
    font-size: 72px;
    color: #cbd5e1;
    margin-bottom: 20px;
}

.empty-state h3 {
    font-family: 'Inter', sans-serif;
    font-size: 22px;
    color: #475569;
    margin-bottom: 10px;
}

.empty-state p {
    font-family: 'Inter', sans-serif;
    font-size: 15px;
    max-width: 400px;
    margin: 0 auto;
}

.time-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 12px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
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

    .request-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }

    .request-time {
        text-align: left;
    }

    .request-actions {
        flex-direction: column;
    }
}
</style>

<div class="main-content">
    <div class="page-header">
        <h1><i class="fas fa-tasks"></i> Faculty Dashboard</h1>
        <p>Review and process student clearance requests</p>
    </div>

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

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon total">
                <i class="fas fa-clipboard-list"></i>
            </div>
            <div class="stat-content">
                <h3><?= $totalAssigned ?></h3>
                <p>Total Assigned</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon cleared">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-content">
                <h3><?= $totalCleared ?></h3>
                <p>Approved</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon requested">
                <i class="fas fa-hourglass-half"></i>
            </div>
            <div class="stat-content">
                <h3><?= $totalRequested ?></h3>
                <p>Pending Review</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon pending">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-content">
                <h3><?= $totalPending ?></h3>
                <p>Not Requested</p>
            </div>
        </div>
    </div>

    <div class="section-header">
        <h2>
            <i class="fas fa-inbox"></i>
            Pending Clearance Requests
            <?php if (count($requests) > 0): ?>
            <span
                style="background: #f59e0b; color: white; padding: 4px 12px; border-radius: 20px; font-size: 14px; margin-left: 10px;">
                <?= count($requests) ?>
            </span>
            <?php endif; ?>
        </h2>
    </div>

    <?php if (empty($requests)): ?>
    <div class="empty-state">
        <i class="fas fa-inbox"></i>
        <h3>No Pending Requests</h3>
        <p>You have no pending clearance requests at the moment. New requests will appear here.</p>
    </div>
    <?php else: ?>

    <?php foreach ($requests as $r): ?>
    <div class="request-card">
        <div class="request-header">
            <div class="request-student">
                <div class="student-avatar">
                    <?= strtoupper(substr($r['student_name'], 0, 1)) ?>
                </div>
                <div class="student-info">
                    <h3><?= htmlspecialchars($r['student_name']) ?></h3>
                    <p><?= htmlspecialchars($r['student_email']) ?></p>
                </div>
            </div>
            <div class="request-time">
                <div class="time-badge">
                    <i class="fas fa-clock"></i>
                    <?= date('M d, Y', strtotime($r['requested_at'])) ?>
                </div>
                <div style="margin-top: 4px; font-size: 12px;">
                    <?= date('h:i A', strtotime($r['requested_at'])) ?>
                </div>
            </div>
        </div>

        <div class="request-body">
            <div class="clearance-info">
                <h4>
                    <i class="fas fa-file-alt"></i>
                    <?= htmlspecialchars($r['clearance_title']) ?>
                </h4>
                <?php if (!empty($r['clearance_description'])): ?>
                <p><?= htmlspecialchars($r['clearance_description']) ?></p>
                <?php endif; ?>
            </div>

            <form method="POST">
                <?= csrf_input() ?>
                <input type="hidden" name="request_id" value="<?= $r['id'] ?>">
                <div class="request-actions">
                    <button type="submit" name="process_request" value="approve" class="btn-approve"
                        onclick="return confirm('Approve clearance for <?= htmlspecialchars($r['student_name']) ?>?')">
                        <i class="fas fa-check"></i>
                        Approve Request
                    </button>
                    <button type="submit" name="process_request" value="reject" class="btn-reject"
                        onclick="return confirm('Reject clearance request from <?= htmlspecialchars($r['student_name']) ?>? The student will need to submit a new request.')">
                        <i class="fas fa-times"></i>
                        Reject Request
                    </button>
                </div>
                <input type="hidden" name="action" id="action_<?= $r['id'] ?>">
            </form>
        </div>
    </div>
    <?php endforeach; ?>

    <?php endif; ?>
</div>

<script>
// Handle form submission with proper action value
document.querySelectorAll('form button[name="process_request"]').forEach(button => {
    button.addEventListener('click', function(e) {
        const form = this.closest('form');
        const actionInput = form.querySelector('input[name="action"]');
        actionInput.value = this.value;
    });
});
</script>

<?php include "footer.php"; ?>