<?php
require_once "../config/connection.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$message = "";
$error = "";

/* =========================
   HANDLE SAVE TEMPLATE
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!csrf_validate()) {
        die("Invalid CSRF token");
    }

    $title          = trim($_POST['title']);
    $academic_year  = trim($_POST['academic_year']);
    $semester       = trim($_POST['semester']);
    $issue_date     = $_POST['issue_date'];
    $header_text    = trim($_POST['header_text']);
    $footer_text    = trim($_POST['footer_text']);
    $clearance_id   = isset($_POST['clearance_id']) ? (int)$_POST['clearance_id'] : null;

    if ($title === "" || $academic_year === "" || $issue_date === "") {
        $error = "Please fill all required fields.";
    } else {

        try {
            $pdo->beginTransaction();

            // Insert template
            $stmt = $pdo->prepare("
                INSERT INTO clearance_templates
                (title, academic_year, semester, header_text, footer_text, issue_date)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $title,
                $academic_year,
                $semester,
                $header_text,
                $footer_text,
                $issue_date
            ]);

            $template_id = $pdo->lastInsertId();

            // If clearance is selected, copy faculty from that clearance
            if ($clearance_id) {
                $copyFaculty = $pdo->prepare("
                    INSERT INTO template_faculty (template_id, faculty_id, position_order)
                    SELECT ?, faculty_id, ROW_NUMBER() OVER (ORDER BY faculty_id)
                    FROM clearance_faculty
                    WHERE clearance_id = ?
                ");
                $copyFaculty->execute([$template_id, $clearance_id]);
            }

            $pdo->commit();
            $message = "Clearance template created successfully.";

        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Failed to save template: " . $e->getMessage();
        }
    }
}

/* =========================
   FETCH CLEARANCES
========================= */
$clearancesStmt = $pdo->query("
    SELECT id, title, description, created_at
    FROM clearances
    ORDER BY created_at DESC
");
$clearances = $clearancesStmt->fetchAll();

/* =========================
   FETCH EXISTING TEMPLATES
========================= */
$templatesStmt = $pdo->query("
    SELECT 
        ct.*,
        COUNT(tf.id) as faculty_count
    FROM clearance_templates ct
    LEFT JOIN template_faculty tf ON tf.template_id = ct.id
    GROUP BY ct.id
    ORDER BY ct.created_at DESC
");
$templates = $templatesStmt->fetchAll();
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

.content-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
}

.form-section {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    overflow: hidden;
}

.section-header {
    padding: 24px 30px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.section-header h2 {
    font-family: 'Inter', sans-serif;
    font-size: 18px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 0;
}

.section-body {
    padding: 30px;
}

.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
}

.form-group {
    margin-bottom: 20px;
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

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    font-family: 'Inter', sans-serif;
    font-size: 14px;
    transition: all 0.2s ease;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.form-group select {
    cursor: pointer;
    background: white;
}

.form-group textarea {
    min-height: 100px;
    resize: vertical;
}

.info-box {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 16px;
    border-radius: 10px;
    color: white;
    margin-bottom: 20px;
}

.info-box p {
    font-family: 'Inter', sans-serif;
    font-size: 13px;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.faculty-preview {
    background: #f8fafc;
    padding: 16px;
    border-radius: 10px;
    border: 2px solid #e2e8f0;
    margin-top: 12px;
}

.faculty-preview h4 {
    font-family: 'Inter', sans-serif;
    font-size: 14px;
    font-weight: 600;
    color: #334155;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.faculty-list {
    display: grid;
    gap: 8px;
}

.faculty-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px;
    background: white;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
}

.faculty-number {
    width: 28px;
    height: 28px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 600;
    flex-shrink: 0;
}

.faculty-name {
    font-family: 'Inter', sans-serif;
    font-size: 13px;
    color: #1e293b;
    font-weight: 500;
}

.empty-state-small {
    text-align: center;
    padding: 20px;
    color: #94a3b8;
    font-family: 'Inter', sans-serif;
    font-size: 13px;
}

.btn-submit {
    width: 100%;
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
    justify-content: center;
    gap: 8px;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.btn-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
}

.templates-list {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    overflow: hidden;
}

.template-item {
    padding: 20px 30px;
    border-bottom: 1px solid #f1f5f9;
    transition: background 0.2s ease;
}

.template-item:hover {
    background: #f8fafc;
}

.template-item:last-child {
    border-bottom: none;
}

.template-header-info {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 12px;
}

.template-title {
    font-family: 'Inter', sans-serif;
    font-size: 16px;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 4px;
}

.template-meta {
    font-family: 'Inter', sans-serif;
    font-size: 12px;
    color: #64748b;
    display: flex;
    gap: 16px;
    flex-wrap: wrap;
}

.template-meta span {
    display: flex;
    align-items: center;
    gap: 4px;
}

.template-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    background: #dbeafe;
    color: #1e40af;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #64748b;
}

.empty-state i {
    font-size: 64px;
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

@media (max-width: 1200px) {
    .content-grid {
        grid-template-columns: 1fr;
    }

    .form-grid {
        grid-template-columns: 1fr;
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

    .section-body {
        padding: 20px;
    }
}
</style>

<div class="main-content">
    <div class="page-header">
        <h1><i class="fas fa-file-invoice"></i> Clearance Template Manager</h1>
        <p>Create reusable clearance templates with predefined faculty signatories</p>
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

    <div class="content-grid">
        <div class="form-section">
            <div class="section-header">
                <h2><i class="fas fa-plus-circle"></i> Create New Template</h2>
            </div>

            <form method="POST" id="templateForm">
                <div class="section-body">
                    <?= csrf_input() ?>

                    <div class="info-box">
                        <p>
                            <i class="fas fa-info-circle"></i>
                            Select an existing clearance to automatically import its faculty signatories
                        </p>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-clipboard-list"></i> Based on Clearance (Optional)</label>
                        <select name="clearance_id" id="clearanceSelect">
                            <option value="">-- Create from scratch --</option>
                            <?php foreach ($clearances as $c): ?>
                            <option value="<?= $c['id'] ?>" data-title="<?= htmlspecialchars($c['title']) ?>">
                                <?= htmlspecialchars($c['title']) ?>
                                (Created: <?= date('M d, Y', strtotime($c['created_at'])) ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div id="facultyPreviewSection" style="display: none;">
                        <div class="faculty-preview">
                            <h4>
                                <i class="fas fa-users"></i>
                                Faculty Signatories
                                <span id="facultyCount"
                                    style="font-size: 12px; color: #667eea; font-weight: 500;"></span>
                            </h4>
                            <div class="faculty-list" id="facultyList">
                                <div class="empty-state-small">
                                    Loading faculty...
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="form-group full-width">
                            <label><i class="fas fa-heading"></i> Template Title *</label>
                            <input type="text" name="title" id="titleInput"
                                placeholder="e.g., End of Semester Clearance Template" required>
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-calendar-alt"></i> Academic Year *</label>
                            <input type="text" name="academic_year" placeholder="2025-2026" required>
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-calendar-check"></i> Semester *</label>
                            <select name="semester" required>
                                <option value="1st Semester">1st Semester</option>
                                <option value="2nd Semester">2nd Semester</option>
                                <option value="Summer">Summer</option>
                            </select>
                        </div>

                        <div class="form-group full-width">
                            <label><i class="fas fa-calendar"></i> Issue Date *</label>
                            <input type="date" name="issue_date" required>
                        </div>

                        <div class="form-group full-width">
                            <label><i class="fas fa-align-left"></i> Header Text</label>
                            <textarea name="header_text"
                                placeholder="Enter header text that will appear at the top of the clearance document..."></textarea>
                        </div>

                        <div class="form-group full-width">
                            <label><i class="fas fa-align-left"></i> Footer Text</label>
                            <textarea name="footer_text"
                                placeholder="Enter footer text that will appear at the bottom of the clearance document..."></textarea>
                        </div>
                    </div>

                    <button type="submit" class="btn-submit">
                        <i class="fas fa-save"></i>
                        Create Template
                    </button>
                </div>
            </form>
        </div>

        <div class="templates-list">
            <div class="section-header">
                <h2><i class="fas fa-list"></i> Existing Templates</h2>
            </div>

            <div class="section-body" style="padding: 0;">
                <?php if (empty($templates)): ?>
                <div class="empty-state">
                    <i class="fas fa-file-alt"></i>
                    <h3>No Templates Yet</h3>
                    <p>Create your first clearance template to get started</p>
                </div>
                <?php else: ?>
                <?php foreach ($templates as $t): ?>
                <div class="template-item">
                    <div class="template-header-info">
                        <div>
                            <div class="template-title"><?= htmlspecialchars($t['title']) ?></div>
                            <div class="template-meta">
                                <span><i class="fas fa-calendar-alt"></i>
                                    <?= htmlspecialchars($t['academic_year']) ?></span>
                                <span><i class="fas fa-graduation-cap"></i>
                                    <?= htmlspecialchars($t['semester']) ?></span>
                                <span><i class="fas fa-calendar"></i>
                                    <?= date('M d, Y', strtotime($t['issue_date'])) ?></span>
                            </div>
                        </div>
                        <span class="template-badge">
                            <i class="fas fa-users"></i>
                            <?= $t['faculty_count'] ?> Signatories
                        </span>
                    </div>
                    <?php if ($t['faculty_count'] > 0): ?>
                    <div style="margin-top: 8px; font-size: 12px; color: #64748b;">
                        <i class="fas fa-check-circle" style="color: #10b981;"></i>
                        Template configured with faculty signatories
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('clearanceSelect').addEventListener('change', async function() {
    const clearanceId = this.value;
    const previewSection = document.getElementById('facultyPreviewSection');
    const facultyList = document.getElementById('facultyList');
    const facultyCount = document.getElementById('facultyCount');
    const titleInput = document.getElementById('titleInput');

    if (!clearanceId) {
        previewSection.style.display = 'none';
        return;
    }

    // Auto-fill title
    const selectedOption = this.options[this.selectedIndex];
    const clearanceTitle = selectedOption.getAttribute('data-title');
    if (clearanceTitle && !titleInput.value) {
        titleInput.value = clearanceTitle + ' - Template';
    }

    previewSection.style.display = 'block';
    facultyList.innerHTML = '<div class="empty-state-small">Loading faculty...</div>';

    try {
        // Fetch faculty for this clearance
        const response = await fetch(`get_clearance_faculty.php?clearance_id=${clearanceId}`);
        const data = await response.json();

        if (data.success && data.faculty.length > 0) {
            facultyCount.textContent = `(${data.faculty.length} faculty)`;
            facultyList.innerHTML = '';

            data.faculty.forEach((faculty, index) => {
                const item = document.createElement('div');
                item.className = 'faculty-item';
                item.innerHTML = `
                        <div class="faculty-number">${index + 1}</div>
                        <div class="faculty-name">
                            ${faculty.fullname}
                            ${faculty.designation ? `<span style="color: #64748b; font-weight: 400;"> - ${faculty.designation}</span>` : ''}
                        </div>
                    `;
                facultyList.appendChild(item);
            });
        } else {
            facultyList.innerHTML =
                '<div class="empty-state-small"><i class="fas fa-info-circle"></i> No faculty assigned to this clearance</div>';
            facultyCount.textContent = '';
        }
    } catch (error) {
        facultyList.innerHTML =
            '<div class="empty-state-small" style="color: #ef4444;"><i class="fas fa-exclamation-circle"></i> Error loading faculty</div>';
        facultyCount.textContent = '';
    }
});
</script>

<?php include "footer.php"; ?>