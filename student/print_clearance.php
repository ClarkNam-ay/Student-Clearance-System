<?php
require_once "../config/connection.php";
require_once "../vendor/dompdf/autoload.inc.php";

use Dompdf\Dompdf;

/*
|--------------------------------------------------------------------------
| SECURITY CHECK
|--------------------------------------------------------------------------
*/
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit;
}

$student_id = $_SESSION['user_id'];

/*
|--------------------------------------------------------------------------
| GET CLEARANCE ID
|--------------------------------------------------------------------------
*/
$clearance_id = isset($_GET['clearance_id']) ? (int)$_GET['clearance_id'] : 3;
if ($clearance_id <= 0) {
    die("Invalid clearance.");
}

/*
|--------------------------------------------------------------------------
| FETCH CLEARANCE
|--------------------------------------------------------------------------
*/
$clearanceStmt = $pdo->prepare("
    SELECT title, description
    FROM clearances
    WHERE id = ?
");
$clearanceStmt->execute([$clearance_id]);
$clearance = $clearanceStmt->fetch();

if (!$clearance) {
    die("Clearance not found.");
}

/*
|--------------------------------------------------------------------------
| FETCH TEMPLATE
|--------------------------------------------------------------------------
*/
$template = $pdo->query("
    SELECT *
    FROM clearance_templates
    ORDER BY created_at DESC
    LIMIT 1
")->fetch();

if (!$template) {
    die("No template found.");
}

/*
|--------------------------------------------------------------------------
| FETCH STUDENT
|--------------------------------------------------------------------------
*/
$studentStmt = $pdo->prepare("
    SELECT fullname, course, year_level, section
    FROM users
    WHERE id = ?
");
$studentStmt->execute([$student_id]);
$student = $studentStmt->fetch();

/*
|--------------------------------------------------------------------------
| FETCH FACULTY + SIGNATURES
|--------------------------------------------------------------------------
*/
$stmt = $pdo->prepare("
    SELECT
        u.fullname,
        u.designation,
        fs.signature_path,
        sc.status,
        sc.cleared_at,
        tf.position_order
    FROM clearance_faculty cf
    JOIN users u ON u.id = cf.faculty_id
    LEFT JOIN student_clearance_status sc
        ON sc.faculty_id = cf.faculty_id
       AND sc.student_id = ?
       AND sc.clearance_id = cf.clearance_id
    LEFT JOIN faculty_signatures fs
        ON fs.faculty_id = cf.faculty_id
    LEFT JOIN template_faculty tf
        ON tf.faculty_id = cf.faculty_id
    WHERE cf.clearance_id = ?
    ORDER BY tf.position_order ASC
");
$stmt->execute([$student_id, $clearance_id]);
$faculty_list = $stmt->fetchAll();

/*
|--------------------------------------------------------------------------
| BUILD HTML (YOUR EXACT VIEW)
|--------------------------------------------------------------------------
*/
ob_start();
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <style>
    /* =========================
   GLOBAL PDF SETTINGS
========================= */
    body {
        font-family: "Times New Roman", DejaVu Sans, serif;
        font-size: 14px;
        color: #000;
        margin: 0;
    }

    .page {
        width: 8.27in;
        min-height: 11.69in;
        padding: 1in;
    }

    /* =========================
   HEADER
========================= */
    .header {
        text-align: center;
        line-height: 1.4;
    }

    .header p {
        margin: 3px 0;
    }

    .header h3 {
        margin: 6px 0;
        font-size: 18px;
        letter-spacing: 0.5px;
    }

    .header h2 {
        margin: 25px 0 15px;
        font-size: 20px;
        text-decoration: underline;
        letter-spacing: 1px;
    }

    /* =========================
   STUDENT INFO
========================= */
    .student-info {
        margin-top: 35px;
        font-size: 15px;
    }

    .student-info p {
        margin: 12px 0;
    }

    .line {
        display: inline-block;
        border-bottom: 1px solid #000;
        min-width: 360px;
        padding-left: 6px;
    }

    /* =========================
   CONTENT
========================= */
    .content {
        margin-top: 35px;
        text-align: justify;
        line-height: 1.7;
        font-size: 15px;
    }

    /* =========================
   SIGNATURES (DOMPDF SAFE)
========================= */
    .signatures {
        margin-top: 50px;
    }

    .signature-row {
        width: 100%;
        margin-bottom: 35px;
        text-align: center;
    }

    .signature-block {
        width: 45%;
        display: inline-block;
        vertical-align: top;
        margin: 0 2%;
    }

    .signature-block img {
        height: 80px;
        margin-bottom: 5px;
    }

    .sig-line {
        border-top: 1px solid #000;
        margin-top: 8px;
        font-weight: bold;
        font-size: 14px;
    }

    .designation {
        font-size: 12px;
    }

    .sig-date {
        font-size: 11px;
        margin-top: 3px;
    }

    /* =========================
   FOOTER
========================= */
    .footer {
        margin-top: 70px;
        text-align: center;
        font-size: 12px;
        line-height: 1.5;
    }
    </style>
</head>

<body>
    <div class="page">

        <div class="header">
            <p>Republic of the Philippines</p>
            <h3>BOHOL ISLAND STATE UNIVERSITY</h3>
            <p>Clarin Campus, Clarin, Bohol</p>
            <p><strong>Student Development Services Office</strong></p>
            <p><?= $template['semester'] ?> Semester A.Y. <?= $template['academic_year'] ?></p>
            <h2>INTERNAL CLEARANCE</h2>
        </div>

        <div class="student-info">
            <p>
                <strong>Name of Student:</strong>
                <span class="line"><?= htmlspecialchars($student['fullname']) ?></span>
            </p>
            <p>
                <strong>Course, Year & Section:</strong>
                <span class="line">
                    <?= htmlspecialchars($student['course']) ?>
                    <?= htmlspecialchars($student['year_level']) ?>
                    <?= htmlspecialchars($student['section']) ?>
                </span>
            </p>
        </div>

        <div class="content">
            <?= nl2br(htmlspecialchars($template['header_text'])) ?><br><br>
            <?= nl2br(htmlspecialchars($clearance['description'])) ?>
        </div>

        <div class="signatures">
            <?php
$counter = 0;
foreach ($faculty_list as $f):
    if ($counter % 2 === 0) echo '<div class="signature-row">';
?>
            <div class="signature-block">
                <?php if ($f['status'] === 'cleared' && !empty($f['signature_path'])): ?>
                <img src="/Student Clearance System/uploads/signatures/<?= basename($f['signature_path']) ?>">

                <?php else: ?>
                <div style="height:80px;"></div>
                <?php endif; ?>

                <div class="sig-line"><?= htmlspecialchars($f['fullname']) ?></div>
                <div class="designation"><?= htmlspecialchars($f['designation']) ?></div>

                <?php if ($f['cleared_at']): ?>
                <div class="sig-date"><?= date('m/d/Y', strtotime($f['cleared_at'])) ?></div>
                <?php endif; ?>
            </div>
            <?php
    if ($counter % 2 === 1) echo '</div>';
    $counter++;
endforeach;
if ($counter % 2 !== 0) echo '</div>';
?>
        </div>


        <div class="footer">
            <?= nl2br(htmlspecialchars($template['footer_text'])) ?>
        </div>

    </div>
</body>

</html>

<?php
$html = ob_get_clean();

/*
|--------------------------------------------------------------------------
| GENERATE & PREVIEW PDF
|--------------------------------------------------------------------------
*/
$pdf = new Dompdf();
$pdf->loadHtml($html);
$pdf->setPaper('A4', 'portrait');
$pdf->render();

$pdf->stream("clearance.pdf", ["Attachment" => false]);
exit;