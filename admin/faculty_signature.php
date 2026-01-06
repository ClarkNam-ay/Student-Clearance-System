<?php 
require_once "../config/connection.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$message = "";
$error = "";
$selected_faculty_id = null;

// Handle signature upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_signature'])) {

    if (!csrf_validate()) {
        die("Invalid CSRF token");
    }

    $faculty_id = (int)$_POST['faculty_id'];
    $selected_faculty_id = $faculty_id;

    // Validate faculty exists and is active
    $checkFaculty = $pdo->prepare("SELECT id FROM users WHERE id = ? AND role = 'faculty' AND status = 'active'");
    $checkFaculty->execute([$faculty_id]);
    
    if (!$checkFaculty->fetch()) {
        $error = "Invalid faculty selected.";
    } elseif (!isset($_FILES['signature']) || $_FILES['signature']['error'] !== UPLOAD_ERR_OK) {
        $error = "Please select a valid signature file.";
    } else {
        $allowed = ['image/png', 'image/jpeg', 'image/jpg'];
        $fileType = strtolower($_FILES['signature']['type']);
        $fileSize = $_FILES['signature']['size'];
        $maxSize = 2 * 1024 * 1024; // 2MB

        if (!in_array($fileType, $allowed)) {
            $error = "Only PNG or JPG images are allowed.";
        } elseif ($fileSize > $maxSize) {
            $error = "File size must be less than 2MB.";
        } else {
            // Create uploads directory if it doesn't exist
            $uploadDir = "../uploads/signatures/";
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            // Delete old signature if exists
            $oldSig = $pdo->prepare("SELECT signature_path FROM faculty_signatures WHERE faculty_id = ?");
            $oldSig->execute([$faculty_id]);
            $old = $oldSig->fetch();
            
            if ($old && file_exists($uploadDir . $old['signature_path'])) {
                unlink($uploadDir . $old['signature_path']);
            }

            // Generate unique filename
            $ext = pathinfo($_FILES['signature']['name'], PATHINFO_EXTENSION);
            $filename = "signature_faculty_{$faculty_id}_" . time() . "." . $ext;
            $path = $uploadDir . $filename;

            if (move_uploaded_file($_FILES['signature']['tmp_name'], $path)) {
                $stmt = $pdo->prepare("
                    INSERT INTO faculty_signatures (faculty_id, signature_path, updated_at)
                    VALUES (?, ?, NOW())
                    ON DUPLICATE KEY UPDATE
                        signature_path = VALUES(signature_path),
                        updated_at = NOW()
                ");
                
                if ($stmt->execute([$faculty_id, $filename])) {
                    $message = "E-signature uploaded successfully!";
                } else {
                    $error = "Failed to save signature to database.";
                }
            } else {
                $error = "Failed to upload file. Please try again.";
            }
        }
    }
}

// Handle signature deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_signature'])) {
    
    if (!csrf_validate()) {
        die("Invalid CSRF token");
    }

    $faculty_id = (int)$_POST['faculty_id'];
    $selected_faculty_id = $faculty_id;

    $oldSig = $pdo->prepare("SELECT signature_path FROM faculty_signatures WHERE faculty_id = ?");
    $oldSig->execute([$faculty_id]);
    $old = $oldSig->fetch();
    
    if ($old) {
        $uploadDir = "../uploads/signatures/";
        if (file_exists($uploadDir . $old['signature_path'])) {
            unlink($uploadDir . $old['signature_path']);
        }
        
        $deleteStmt = $pdo->prepare("DELETE FROM faculty_signatures WHERE faculty_id = ?");
        if ($deleteStmt->execute([$faculty_id])) {
            $message = "E-signature deleted successfully.";
        } else {
            $error = "Failed to delete signature.";
        }
    }
}

// Get all faculties
$faculties = $pdo->query("
    SELECT u.id, u.fullname, u.designation, fs.signature_path, fs.updated_at
    FROM users u
    LEFT JOIN faculty_signatures fs ON fs.faculty_id = u.id
    WHERE u.role = 'faculty' AND u.status = 'active'
    ORDER BY u.fullname
")->fetchAll();

// Get current signature if faculty is selected
$currentSignature = null;
if ($selected_faculty_id) {
    $existing = $pdo->prepare("
        SELECT signature_path, updated_at
        FROM faculty_signatures
        WHERE faculty_id = ?
    ");
    $existing->execute([$selected_faculty_id]);
    $currentSignature = $existing->fetch();
}
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

.upload-section {
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

.form-group {
    margin-bottom: 24px;
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

.form-group select {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    font-family: 'Inter', sans-serif;
    font-size: 14px;
    transition: all 0.2s ease;
    cursor: pointer;
    background: white;
}

.form-group select:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.file-upload-wrapper {
    position: relative;
    width: 100%;
}

.file-upload-label {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 40px 20px;
    border: 2px dashed #cbd5e1;
    border-radius: 10px;
    background: #f8fafc;
    cursor: pointer;
    transition: all 0.2s ease;
}

.file-upload-label:hover {
    border-color: #667eea;
    background: #f0f4ff;
}

.file-upload-label i {
    font-size: 48px;
    color: #94a3b8;
    margin-bottom: 12px;
}

.file-upload-label p {
    font-family: 'Inter', sans-serif;
    font-size: 14px;
    color: #64748b;
    margin-bottom: 4px;
}

.file-upload-label span {
    font-family: 'Inter', sans-serif;
    font-size: 12px;
    color: #94a3b8;
}

.file-upload-input {
    display: none;
}

.file-preview {
    margin-top: 16px;
    padding: 12px;
    background: #f0fdf4;
    border: 2px solid #bbf7d0;
    border-radius: 8px;
    display: none;
    align-items: center;
    gap: 12px;
}

.file-preview.active {
    display: flex;
}

.file-preview i {
    color: #059669;
    font-size: 20px;
}

.file-preview-info {
    flex: 1;
}

.file-preview-info p {
    font-family: 'Inter', sans-serif;
    font-size: 13px;
    color: #065f46;
    font-weight: 600;
    margin: 0;
}

.file-preview-info span {
    font-family: 'Inter', sans-serif;
    font-size: 11px;
    color: #059669;
}

.btn-remove-file {
    background: #fee2e2;
    color: #991b1b;
    border: none;
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 12px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-remove-file:hover {
    background: #fecaca;
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

.btn-submit:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none;
}

.current-signature {
    background: #f8fafc;
    padding: 20px;
    border-radius: 10px;
    border: 2px solid #e2e8f0;
    margin-top: 20px;
}

.current-signature h4 {
    font-family: 'Inter', sans-serif;
    font-size: 14px;
    font-weight: 600;
    color: #334155;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.signature-preview {
    background: white;
    padding: 20px;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    text-align: center;
    margin-bottom: 12px;
}

.signature-preview img {
    max-width: 100%;
    max-height: 150px;
    object-fit: contain;
}

.signature-meta {
    font-family: 'Inter', sans-serif;
    font-size: 12px;
    color: #64748b;
    margin-bottom: 12px;
}

.btn-delete {
    width: 100%;
    background: #ef4444;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 8px;
    font-family: 'Inter', sans-serif;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.btn-delete:hover {
    background: #dc2626;
}

.faculty-list-section {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    overflow: hidden;
}

.faculty-grid {
    max-height: 600px;
    overflow-y: auto;
}

.faculty-item {
    padding: 20px 30px;
    border-bottom: 1px solid #f1f5f9;
    display: flex;
    align-items: center;
    justify-content: space-between;
    transition: background 0.2s ease;
}

.faculty-item:hover {
    background: #f8fafc;
}

.faculty-info-wrapper {
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

.signature-status {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 6px 12px;
    border-radius: 20px;
    font-family: 'Inter', sans-serif;
    font-size: 12px;
    font-weight: 600;
}

.signature-status.has-signature {
    background: #d1fae5;
    color: #065f46;
}

.signature-status.no-signature {
    background: #fee2e2;
    color: #991b1b;
}

@media (max-width: 1200px) {
    .content-grid {
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
        <h1><i class="fas fa-signature"></i> E-Signature Management</h1>
        <p>Upload and manage digital signatures for faculty members</p>
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
        <div class="upload-section">
            <div class="section-header">
                <h2><i class="fas fa-upload"></i> Upload Signature</h2>
            </div>

            <form method="POST" enctype="multipart/form-data" id="signatureForm">
                <div class="section-body">
                    <?= csrf_input() ?>

                    <div class="form-group">
                        <label><i class="fas fa-user-tie"></i> Select Faculty Member *</label>
                        <select name="faculty_id" id="facultySelect" required>
                            <option value="">-- Choose Faculty --</option>
                            <?php foreach ($faculties as $f): ?>
                            <option value="<?= $f['id'] ?>" data-has-signature="<?= $f['signature_path'] ? '1' : '0' ?>"
                                data-signature-path="<?= htmlspecialchars($f['signature_path'] ?? '') ?>"
                                data-updated-at="<?= htmlspecialchars($f['updated_at'] ?? '') ?>">
                                <?= htmlspecialchars($f['fullname']) ?>
                                <?= $f['designation'] ? '(' . htmlspecialchars($f['designation']) . ')' : '' ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-image"></i> Upload Signature Image *</label>
                        <div class="file-upload-wrapper">
                            <label for="signatureFile" class="file-upload-label">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <p>Click to upload or drag and drop</p>
                                <span>PNG or JPG (Max 2MB)</span>
                            </label>
                            <input type="file" name="signature" id="signatureFile" class="file-upload-input"
                                accept="image/png, image/jpeg, image/jpg" required onchange="handleFileSelect(this)">

                            <div class="file-preview" id="filePreview">
                                <i class="fas fa-file-image"></i>
                                <div class="file-preview-info">
                                    <p id="fileName"></p>
                                    <span id="fileSize"></span>
                                </div>
                                <button type="button" class="btn-remove-file" onclick="removeFile()">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div id="currentSignatureSection" style="display: none;">
                        <div class="current-signature">
                            <h4><i class="fas fa-image"></i> Current Signature</h4>
                            <div class="signature-preview">
                                <img id="currentSigImage" src="" alt="Current Signature">
                            </div>
                            <div class="signature-meta" id="currentSigMeta">
                            </div>
                            <button type="button" class="btn-delete" onclick="deleteSignature()">
                                <i class="fas fa-trash-alt"></i>
                                Delete Signature
                            </button>
                        </div>
                    </div>

                    <button type="submit" name="upload_signature" class="btn-submit" id="submitBtn">
                        <i class="fas fa-save"></i>
                        <span id="submitBtnText">Upload Signature</span>
                    </button>
                </div>
            </form>

            <form method="POST" id="deleteForm" style="display: none;">
                <?= csrf_input() ?>
                <input type="hidden" name="faculty_id" id="deleteFacultyId">
                <input type="hidden" name="delete_signature" value="1">
            </form>
        </div>

        <div class="faculty-list-section">
            <div class="section-header">
                <h2><i class="fas fa-list"></i> Faculty Signature Status</h2>
            </div>

            <div class="faculty-grid">
                <?php foreach ($faculties as $f): ?>
                <div class="faculty-item">
                    <div class="faculty-info-wrapper">
                        <div class="faculty-avatar">
                            <?= strtoupper(substr($f['fullname'], 0, 1)) ?>
                        </div>
                        <div class="faculty-details">
                            <h4><?= htmlspecialchars($f['fullname']) ?></h4>
                            <p><?= htmlspecialchars($f['designation'] ?? 'Faculty Member') ?></p>
                        </div>
                    </div>
                    <?php if ($f['signature_path']): ?>
                    <span class="signature-status has-signature">
                        <i class="fas fa-check-circle"></i>
                        Uploaded
                    </span>
                    <?php else: ?>
                    <span class="signature-status no-signature">
                        <i class="fas fa-times-circle"></i>
                        No Signature
                    </span>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script>
// Handle faculty selection to show/hide current signature
document.getElementById('facultySelect').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const hasSignature = selectedOption.getAttribute('data-has-signature') === '1';
    const signaturePath = selectedOption.getAttribute('data-signature-path');
    const updatedAt = selectedOption.getAttribute('data-updated-at');

    const currentSigSection = document.getElementById('currentSignatureSection');
    const submitBtnText = document.getElementById('submitBtnText');

    if (hasSignature && signaturePath) {
        // Show current signature
        document.getElementById('currentSigImage').src = '../uploads/signatures/' + signaturePath;
        document.getElementById('currentSigMeta').innerHTML = '<i class="fas fa-clock"></i> Last updated: ' +
            formatDate(updatedAt);
        document.getElementById('deleteFacultyId').value = selectedOption.value;
        currentSigSection.style.display = 'block';
        submitBtnText.textContent = 'Update Signature';
    } else {
        // Hide current signature
        currentSigSection.style.display = 'none';
        submitBtnText.textContent = 'Upload Signature';
    }
});

function deleteSignature() {
    if (confirm('Delete this signature? This action cannot be undone.')) {
        document.getElementById('deleteForm').submit();
    }
}

function formatDate(dateString) {
    if (!dateString) return '';
    const date = new Date(dateString);
    const options = {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    };
    return date.toLocaleDateString('en-US', options);
}

function handleFileSelect(input) {
    const file = input.files[0];
    if (file) {
        const filePreview = document.getElementById('filePreview');
        const fileName = document.getElementById('fileName');
        const fileSize = document.getElementById('fileSize');

        fileName.textContent = file.name;
        fileSize.textContent = formatFileSize(file.size);
        filePreview.classList.add('active');

        // Update upload label
        const label = document.querySelector('.file-upload-label');
        label.style.borderColor = '#667eea';
        label.style.background = '#f0f4ff';
    }
}

function removeFile() {
    const input = document.getElementById('signatureFile');
    input.value = '';

    const filePreview = document.getElementById('filePreview');
    filePreview.classList.remove('active');

    // Reset upload label
    const label = document.querySelector('.file-upload-label');
    label.style.borderColor = '#cbd5e1';
    label.style.background = '#f8fafc';
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
}

// Drag and drop functionality
const uploadLabel = document.querySelector('.file-upload-label');
const fileInput = document.getElementById('signatureFile');

['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
    uploadLabel.addEventListener(eventName, preventDefaults, false);
});

function preventDefaults(e) {
    e.preventDefault();
    e.stopPropagation();
}

['dragenter', 'dragover'].forEach(eventName => {
    uploadLabel.addEventListener(eventName, () => {
        uploadLabel.style.borderColor = '#667eea';
        uploadLabel.style.background = '#f0f4ff';
    }, false);
});

['dragleave', 'drop'].forEach(eventName => {
    uploadLabel.addEventListener(eventName, () => {
        uploadLabel.style.borderColor = '#cbd5e1';
        uploadLabel.style.background = '#f8fafc';
    }, false);
});

uploadLabel.addEventListener('drop', (e) => {
    const dt = e.dataTransfer;
    const files = dt.files;
    fileInput.files = files;
    handleFileSelect(fileInput);
});

// Form validation
document.getElementById('signatureForm').addEventListener('submit', function(e) {
    const facultyId = document.getElementById('facultySelect').value;
    const fileInput = document.getElementById('signatureFile');

    if (!facultyId) {
        e.preventDefault();
        alert('Please select a faculty member.');
        return false;
    }

    if (!fileInput.files.length) {
        e.preventDefault();
        alert('Please select a signature file.');
        return false;
    }
});
</script>

<?php include "footer.php"; ?>