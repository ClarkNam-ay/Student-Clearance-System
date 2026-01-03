<?php
require_once "../config/connection.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit;
}

$student_id = $_SESSION['user_id'];
$message = "";
$error = "";

// Handle clearance request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_clearance'])) {
    
    if (!csrf_validate()) {
        die("Invalid CSRF token");
    }

    $clearance_id = (int)$_POST['clearance_id'];
    $faculty_id   = (int)$_POST['faculty_id'];

    // Check if already requested or cleared
    $check = $pdo->prepare("
        SELECT status FROM student_clearance_status
        WHERE student_id = ? AND clearance_id = ? AND faculty_id = ?
    ");
    $check->execute([$student_id, $clearance_id, $faculty_id]);
    $current = $check->fetch();

    if ($current && $current['status'] === 'pending') {
        $stmt = $pdo->prepare("
            UPDATE student_clearance_status
            SET status = 'requested',
                requested_at = NOW()
            WHERE student_id = ? AND clearance_id = ? AND faculty_id = ?
        ");
        
        if ($stmt->execute([$student_id, $clearance_id, $faculty_id])) {
            $message = "Clearance request sent successfully!";
        } else {
            $error = "Failed to send request. Please try again.";
        }
    } elseif ($current && $current['status'] === 'requested') {
        $error = "You have already requested this clearance.";
    } elseif ($current && $current['status'] === 'cleared') {
        $error = "This clearance has already been approved.";
    }
}

$stmt = $pdo->prepare("
    SELECT 
        sc.clearance_id,
        c.title AS clearance_title,
        c.description,
        c.created_at,
        sc.faculty_id,
        u.fullname AS faculty_name,
        u.designation,
        sc.status,
        sc.cleared_at,
        sc.requested_at
    FROM student_clearance_status sc
    JOIN clearances c ON c.id = sc.clearance_id
    JOIN users u ON u.id = sc.faculty_id
    WHERE sc.student_id = ?
    ORDER BY c.created_at DESC, u.fullname
");
$stmt->execute([$student_id]);
$records = $stmt->fetchAll();

$clearances = [];
$totalCleared = 0;
$totalPending = 0;
$totalRequested = 0;

foreach ($records as $r) {
    $clearances[$r['clearance_id']]['title'] = $r['clearance_title'];
    $clearances[$r['clearance_id']]['description'] = $r['description'];
    $clearances[$r['clearance_id']]['created_at'] = $r['created_at'];
    $clearances[$r['clearance_id']]['items'][] = $r;
    
    if ($r['status'] === 'cleared') {
        $totalCleared++;
    } elseif ($r['status'] === 'requested') {
        $totalRequested++;
    } else {
        $totalPending++;
    }
}

// Calculate completion percentage
$totalItems = $totalCleared + $totalPending + $totalRequested;
$completionPercentage = $totalItems > 0 ? round(($totalCleared / $totalItems) * 100) : 0;
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

.stat-icon.cleared {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

.stat-icon.requested {
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
}

.stat-icon.pending {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
}

.stat-icon.progress {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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

.progress-bar-container {
    background: #e2e8f0;
    height: 8px;
    border-radius: 10px;
    overflow: hidden;
    margin-top: 8px;
}

.progress-bar {
    height: 100%;
    background: linear-gradient(90deg, #10b981 0%, #059669 100%);
    border-radius: 10px;
    transition: width 0.5s ease;
}

.clearance-card {
    background: white;
    padding: 0;
    margin-bottom: 24px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    overflow: hidden;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.clearance-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
}

.clearance-header {
    padding: 24px 30px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.clearance-header h2 {
    font-family: 'Inter', sans-serif;
    font-size: 20px;
    font-weight: 600;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.clearance-header p {
    font-size: 14px;
    opacity: 0.95;
    margin-bottom: 12px;
}

.clearance-meta {
    display: flex;
    align-items: center;
    gap: 16px;
    font-size: 13px;
    opacity: 0.9;
}

.clearance-meta span {
    display: flex;
    align-items: center;
    gap: 6px;
}

.clearance-body {
    padding: 24px 30px;
}

.faculty-list {
    display: grid;
    gap: 12px;
}

.faculty-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px;
    background: #f8fafc;
    border-radius: 10px;
    border: 2px solid #e2e8f0;
    transition: all 0.2s ease;
}

.faculty-item:hover {
    background: white;
    border-color: #cbd5e1;
}

.faculty-item.cleared {
    background: #f0fdf4;
    border-color: #bbf7d0;
}

.faculty-item.requested {
    background: #eff6ff;
    border-color: #bfdbfe;
}

.faculty-info {
    display: flex;
    align-items: center;
    gap: 12px;
    flex: 1;
}

.faculty-avatar {
    width: 44px;
    height: 44px;
    border-radius: 10px;
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

.faculty-details h4 {
    font-family: 'Inter', sans-serif;
    font-size: 15px;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 3px;
}

.faculty-details p {
    font-family: 'Inter', sans-serif;
    font-size: 13px;
    color: #64748b;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    border-radius: 20px;
    font-family: 'Inter', sans-serif;
    font-size: 13px;
    font-weight: 600;
    white-space: nowrap;
}

.status-badge.pending {
    background: #fef3c7;
    color: #92400e;
}

.status-badge.requested {
    background: #dbeafe;
    color: #1e40af;
}

.status-badge.cleared {
    background: #d1fae5;
    color: #065f46;
}

.status-badge i {
    font-size: 14px;
}

.btn-request {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 10px;
    font-family: 'Inter', sans-serif;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.btn-request:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
}

.btn-request:active {
    transform: translateY(0);
}

.cleared-info,
.requested-info {
    font-size: 11px;
    margin-top: 4px;
    display: flex;
    align-items: center;
    gap: 4px;
}

.cleared-info {
    color: #059669;
}

.requested-info {
    color: #2563eb;
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

.clearance-progress {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px;
    background: #f8fafc;
    border-radius: 8px;
    margin-bottom: 16px;
    font-family: 'Inter', sans-serif;
}

.clearance-progress-text {
    font-size: 14px;
    color: #64748b;
    font-weight: 500;
}

.clearance-progress-count {
    font-size: 14px;
    font-weight: 600;
    color: #1e293b;
}

.clearance-progress-count .cleared-num {
    color: #059669;
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

    .clearance-header {
        padding: 20px;
    }

    .clearance-body {
        padding: 20px;
    }

    .faculty-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }

    .status-badge,
    .btn-request {
        align-self: flex-end;
    }
}
</style>

<div class="main-content">
    <div class="page-header">
        <h1><i class="fas fa-clipboard-check"></i> My Clearances</h1>
        <p>Track your clearance status and requirements</p>
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
            <div class="stat-icon cleared">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-content">
                <h3><?= $totalCleared ?></h3>
                <p>Cleared</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon requested">
                <i class="fas fa-paper-plane"></i>
            </div>
            <div class="stat-content">
                <h3><?= $totalRequested ?></h3>
                <p>Requested</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon pending">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-content">
                <h3><?= $totalPending ?></h3>
                <p>Pending</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon progress">
                <i class="fas fa-chart-line"></i>
            </div>
            <div class="stat-content">
                <h3><?= $completionPercentage ?>%</h3>
                <p>Completion Rate</p>
                <div class="progress-bar-container">
                    <div class="progress-bar" style="width: <?= $completionPercentage ?>%"></div>
                </div>
            </div>
        </div>
    </div>

    <?php if (empty($clearances)): ?>
    <div class="empty-state">
        <i class="fas fa-clipboard-list"></i>
        <h3>No Clearances Yet</h3>
        <p>You don't have any clearance requirements at the moment. Check back later for updates.</p>
    </div>
    <?php else: ?>

    <?php foreach ($clearances as $cid => $clearance): ?>
    <div class="clearance-card">
        <div class="clearance-header">
            <h2>
                <i class="fas fa-file-alt"></i>
                <?= htmlspecialchars($clearance['title']) ?>
            </h2>
            <?php if (!empty($clearance['description'])): ?>
            <p><?= htmlspecialchars($clearance['description']) ?></p>
            <?php endif; ?>
            <div class="clearance-meta">
                <span>
                    <i class="fas fa-calendar-alt"></i>
                    <?= date('M d, Y', strtotime($clearance['created_at'])) ?>
                </span>
                <span>
                    <i class="fas fa-users"></i>
                    <?= count($clearance['items']) ?> Faculty Approvers
                </span>
            </div>
        </div>

        <div class="clearance-body">
            <?php
            $cleared = count(array_filter($clearance['items'], fn($i) => $i['status'] === 'cleared'));
            $total = count($clearance['items']);
            $percentage = round(($cleared / $total) * 100);
            ?>

            <div class="clearance-progress">
                <span class="clearance-progress-text">Progress</span>
                <span class="clearance-progress-count">
                    <span class="cleared-num"><?= $cleared ?></span> / <?= $total ?> Cleared
                </span>
            </div>

            <div class="faculty-list">
                <?php foreach ($clearance['items'] as $item): ?>
                <div class="faculty-item <?= $item['status'] ?>">
                    <div class="faculty-info">
                        <div class="faculty-avatar">
                            <?= strtoupper(substr($item['faculty_name'], 0, 1)) ?>
                        </div>
                        <div class="faculty-details">
                            <h4><?= htmlspecialchars($item['faculty_name']) ?></h4>
                            <p><?= htmlspecialchars($item['designation'] ?? 'Faculty Member') ?></p>
                            <?php if ($item['status'] === 'cleared' && $item['cleared_at']): ?>
                            <div class="cleared-info">
                                <i class="fas fa-check"></i>
                                Cleared on <?= date('M d, Y', strtotime($item['cleared_at'])) ?>
                            </div>
                            <?php elseif ($item['status'] === 'requested' && $item['requested_at']): ?>
                            <div class="requested-info">
                                <i class="fas fa-paper-plane"></i>
                                Requested on <?= date('M d, Y', strtotime($item['requested_at'])) ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if ($item['status'] === 'pending'): ?>
                    <form method="POST" style="display: inline;">
                        <?= csrf_input() ?>
                        <input type="hidden" name="clearance_id" value="<?= $cid ?>">
                        <input type="hidden" name="faculty_id" value="<?= $item['faculty_id'] ?>">
                        <button type="submit" name="request_clearance" class="btn-request"
                            onclick="return confirm('Send clearance request to <?= htmlspecialchars($item['faculty_name']) ?>?')">
                            <i class="fas fa-paper-plane"></i>
                            Request Clearance
                        </button>
                    </form>
                    <?php elseif ($item['status'] === 'requested'): ?>
                    <span class="status-badge requested">
                        <i class="fas fa-hourglass-half"></i>
                        Awaiting Approval
                    </span>
                    <?php elseif ($item['status'] === 'cleared'): ?>
                    <span class="status-badge cleared">
                        <i class="fas fa-check-circle"></i>
                        Cleared
                    </span>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <?php endif; ?>
</div>

<?php include "footer.php"; ?>