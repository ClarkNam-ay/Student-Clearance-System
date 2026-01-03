<?php 
require_once "../config/connection.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$message = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_clearance'])) {

    if (!csrf_validate()) die("Invalid CSRF token");

    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $faculty_ids = $_POST['faculty'] ?? [];

    if ($title === "" || empty($faculty_ids)) {
        $error = "Clearance title and faculty selection are required.";
    } else {

        // 1️⃣ Create clearance
        $stmt = $pdo->prepare(
            "INSERT INTO clearances (title, description) VALUES (?, ?)"
        );
        $stmt->execute([$title, $description]);
        $clearance_id = $pdo->lastInsertId();

        // 2️⃣ Assign faculty
        $cf = $pdo->prepare(
            "INSERT INTO clearance_faculty (clearance_id, faculty_id)
             VALUES (?, ?)"
        );

        foreach ($faculty_ids as $fid) {
            $cf->execute([$clearance_id, $fid]);
        }

        // 3️⃣ Fetch all students
        $students = $pdo->query(
            "SELECT id FROM users WHERE role='student' AND status='active'"
        )->fetchAll();

        // 4️⃣ Create student clearance records
        $sc = $pdo->prepare(
            "INSERT INTO student_clearance_status 
             (student_id, clearance_id, faculty_id)
             VALUES (?, ?, ?)"
        );

        foreach ($students as $s) {
            foreach ($faculty_ids as $fid) {
                $sc->execute([$s['id'], $clearance_id, $fid]);
            }
        }

        $message = "Clearance created and distributed to all students.";
    }
}

$facultyList = $pdo->query(
    "SELECT id, fullname, designation FROM users 
     WHERE role='faculty' AND status='active'
     ORDER BY fullname"
)->fetchAll();

// Get active students count
$studentCount = $pdo->query(
    "SELECT COUNT(*) FROM users WHERE role='student' AND status='active'"
)->fetchColumn();
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

.form-container {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    overflow: hidden;
}

.form-header {
    padding: 24px 30px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.form-header h2 {
    font-family: 'Inter', sans-serif;
    font-size: 18px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 0;
}

.form-body {
    padding: 30px;
}

.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
    margin-bottom: 24px;
}

.form-group {
    margin-bottom: 24px;
}

.form-group.full-width {
    grid-column: 1 / -1;
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
    width: 16px;
    text-align: center;
}

.form-group input[type="text"],
.form-group textarea {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    font-family: 'Inter', sans-serif;
    font-size: 14px;
    transition: all 0.2s ease;
}

.form-group input[type="text"]:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.form-group textarea {
    min-height: 120px;
    resize: vertical;
}

.faculty-selection {
    background: #f8fafc;
    padding: 20px;
    border-radius: 10px;
    border: 2px solid #e2e8f0;
}

.faculty-selection-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
}

.faculty-selection-header h3 {
    font-family: 'Inter', sans-serif;
    font-size: 14px;
    font-weight: 600;
    color: #334155;
    display: flex;
    align-items: center;
    gap: 8px;
}

.select-all-btn {
    background: #667eea;
    color: white;
    border: none;
    padding: 6px 12px;
    border-radius: 6px;
    font-family: 'Inter', sans-serif;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
}

.select-all-btn:hover {
    background: #5568d3;
}

.faculty-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 12px;
    max-height: 400px;
    overflow-y: auto;
    padding-right: 10px;
}

.faculty-grid::-webkit-scrollbar {
    width: 6px;
}

.faculty-grid::-webkit-scrollbar-track {
    background: #e2e8f0;
    border-radius: 10px;
}

.faculty-grid::-webkit-scrollbar-thumb {
    background: #94a3b8;
    border-radius: 10px;
}

.faculty-item {
    background: white;
    padding: 14px;
    border-radius: 8px;
    border: 2px solid #e2e8f0;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    gap: 12px;
}

.faculty-item:hover {
    border-color: #667eea;
    background: #f8fafc;
}

.faculty-item.selected {
    border-color: #667eea;
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
}

.faculty-checkbox {
    width: 20px;
    height: 20px;
    border: 2px solid #cbd5e1;
    border-radius: 6px;
    cursor: pointer;
    flex-shrink: 0;
    accent-color: #667eea;
}

.faculty-info {
    flex: 1;
}

.faculty-info h4 {
    font-family: 'Inter', sans-serif;
    font-size: 14px;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 2px;
}

.faculty-info p {
    font-family: 'Inter', sans-serif;
    font-size: 12px;
    color: #64748b;
}

.faculty-avatar {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
    font-size: 14px;
    font-family: 'Inter', sans-serif;
    flex-shrink: 0;
}

.info-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 20px;
    border-radius: 10px;
    color: white;
    margin-bottom: 24px;
}

.info-card-content {
    display: flex;
    align-items: center;
    gap: 16px;
}

.info-card-icon {
    width: 48px;
    height: 48px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
}

.info-card-text h3 {
    font-size: 28px;
    font-weight: 700;
    margin-bottom: 4px;
}

.info-card-text p {
    font-size: 14px;
    opacity: 0.9;
}

.form-footer {
    padding: 20px 30px;
    background: #f8fafc;
    display: flex;
    gap: 12px;
    justify-content: space-between;
    align-items: center;
}

.selected-count {
    font-family: 'Inter', sans-serif;
    font-size: 14px;
    color: #64748b;
    display: flex;
    align-items: center;
    gap: 8px;
}

.selected-count span {
    background: #667eea;
    color: white;
    padding: 4px 12px;
    border-radius: 12px;
    font-weight: 600;
    font-size: 12px;
}

.btn-submit {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 14px 28px;
    border: none;
    border-radius: 10px;
    font-family: 'Inter', sans-serif;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.btn-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
}

.btn-submit:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none;
}

.empty-state {
    text-align: center;
    padding: 40px 20px;
    color: #64748b;
}

.empty-state i {
    font-size: 48px;
    color: #cbd5e1;
    margin-bottom: 16px;
}

.empty-state h3 {
    font-family: 'Inter', sans-serif;
    font-size: 18px;
    color: #475569;
    margin-bottom: 8px;
}

.empty-state p {
    font-family: 'Inter', sans-serif;
    font-size: 14px;
}

@media (max-width: 1024px) {
    .main-content {
        margin-left: 0;
        padding: 20px;
    }

    .form-grid {
        grid-template-columns: 1fr;
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

    .form-body {
        padding: 20px;
    }

    .faculty-grid {
        grid-template-columns: 1fr;
    }

    .form-footer {
        flex-direction: column;
        align-items: stretch;
    }

    .btn-submit {
        justify-content: center;
    }
}
</style>

<div class="main-content">
    <div class="page-header">
        <h1><i class="fas fa-clipboard-check"></i> Create Clearance</h1>
        <p>Create and distribute clearance requirements to students</p>
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

    <form method="POST" id="clearanceForm">
        <?= csrf_input() ?>

        <div class="form-container">
            <div class="form-header">
                <h2><i class="fas fa-edit"></i> Clearance Details</h2>
            </div>

            <div class="form-body">
                <div class="info-card">
                    <div class="info-card-content">
                        <div class="info-card-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="info-card-text">
                            <h3><?= $studentCount ?></h3>
                            <p>Active students will receive this clearance</p>
                        </div>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group full-width">
                        <label><i class="fas fa-heading"></i> Clearance Title *</label>
                        <input type="text" name="title" placeholder="e.g., End of Semester Clearance 2024" required>
                    </div>

                    <div class="form-group full-width">
                        <label><i class="fas fa-align-left"></i> Description</label>
                        <textarea name="description"
                            placeholder="Enter clearance description or requirements..."></textarea>
                    </div>

                    <div class="form-group full-width">
                        <label><i class="fas fa-user-check"></i> Select Faculty Approvers *</label>

                        <?php if (empty($facultyList)): ?>
                        <div class="empty-state">
                            <i class="fas fa-user-times"></i>
                            <h3>No Faculty Available</h3>
                            <p>There are no active faculty members to assign. Please create faculty accounts first.</p>
                        </div>
                        <?php else: ?>
                        <div class="faculty-selection">
                            <div class="faculty-selection-header">
                                <h3>
                                    <i class="fas fa-list-check"></i>
                                    Available Faculty (<span id="facultyCount"><?= count($facultyList) ?></span>)
                                </h3>
                                <button type="button" class="select-all-btn" onclick="toggleSelectAll()">
                                    <i class="fas fa-check-double"></i> Select All
                                </button>
                            </div>

                            <div class="faculty-grid">
                                <?php foreach ($facultyList as $f): ?>
                                <label class="faculty-item" onclick="updateSelection()">
                                    <input type="checkbox" name="faculty[]" value="<?= $f['id'] ?>"
                                        class="faculty-checkbox">
                                    <div class="faculty-avatar">
                                        <?= strtoupper(substr($f['fullname'], 0, 1)) ?>
                                    </div>
                                    <div class="faculty-info">
                                        <h4><?= htmlspecialchars($f['fullname']) ?></h4>
                                        <p><?= htmlspecialchars($f['designation'] ?? 'Faculty Member') ?></p>
                                    </div>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="form-footer">
                <div class="selected-count">
                    <i class="fas fa-info-circle"></i>
                    Selected Faculty: <span id="selectedCount">0</span>
                </div>
                <button type="submit" name="create_clearance" class="btn-submit" id="submitBtn"
                    <?= empty($facultyList) ? 'disabled' : '' ?>>
                    <i class="fas fa-paper-plane"></i>
                    Create & Distribute Clearance
                </button>
            </div>
        </div>
    </form>
</div>

<script>
function updateSelection() {
    setTimeout(() => {
        const checkboxes = document.querySelectorAll('.faculty-checkbox');
        const selectedCount = document.querySelectorAll('.faculty-checkbox:checked').length;
        const submitBtn = document.getElementById('submitBtn');

        document.getElementById('selectedCount').textContent = selectedCount;

        // Update selected state styling
        checkboxes.forEach(checkbox => {
            const item = checkbox.closest('.faculty-item');
            if (checkbox.checked) {
                item.classList.add('selected');
            } else {
                item.classList.remove('selected');
            }
        });

        // Enable/disable submit button
        if (selectedCount > 0) {
            submitBtn.disabled = false;
        } else {
            submitBtn.disabled = true;
        }
    }, 10);
}

function toggleSelectAll() {
    const checkboxes = document.querySelectorAll('.faculty-checkbox');
    const allChecked = Array.from(checkboxes).every(cb => cb.checked);

    checkboxes.forEach(checkbox => {
        checkbox.checked = !allChecked;
    });

    updateSelection();

    const btn = document.querySelector('.select-all-btn');
    if (allChecked) {
        btn.innerHTML = '<i class="fas fa-check-double"></i> Select All';
    } else {
        btn.innerHTML = '<i class="fas fa-times"></i> Deselect All';
    }
}

// Initialize selection count on page load
document.addEventListener('DOMContentLoaded', function() {
    updateSelection();

    // Add change event listeners to all checkboxes
    document.querySelectorAll('.faculty-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', updateSelection);
    });
});

// Form validation
document.getElementById('clearanceForm').addEventListener('submit', function(e) {
    const selectedCount = document.querySelectorAll('.faculty-checkbox:checked').length;

    if (selectedCount === 0) {
        e.preventDefault();
        alert('Please select at least one faculty member to approve this clearance.');
        return false;
    }

    const confirmed = confirm(
        `You are about to create a clearance that will be distributed to ${<?= $studentCount ?>} active students and require approval from ${selectedCount} faculty member(s).\n\nContinue?`
    );

    if (!confirmed) {
        e.preventDefault();
        return false;
    }
});
</script>

<?php include "footer.php"; ?>