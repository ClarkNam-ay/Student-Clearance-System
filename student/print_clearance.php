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
| BUILD HTML (CENTERED LAYOUT LIKE REFERENCE)
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
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: "Times New Roman", Georgia, serif;
        font-size: 12px;
        color: #000;
        line-height: 1.4;
    }

    .page {
        width: 8.27in;
        min-height: 11.69in;
        padding: 0.6in 0.8in;
    }

    /* =========================
   HEADER - CENTERED
========================= */
    .header {
        text-align: center;
        margin-bottom: 30px;
    }

    .header p {
        margin: 2px 0;
        font-size: 11px;
    }

    .header h3 {
        margin: 5px 0;
        font-size: 16px;
        font-weight: bold;
        letter-spacing: 0.5px;
    }

    .header .office-title {
        font-size: 12px;
        font-weight: bold;
        margin: 8px 0;
        text-transform: uppercase;
    }

    .header .semester-info {
        font-size: 11px;
        margin: 10px 0;
        text-decoration: underline;
    }

    .header h2 {
        margin: 20px 0 30px;
        font-size: 18px;
        font-weight: bold;
        letter-spacing: 1px;
    }

    /* =========================
   STUDENT INFO - LEFT ALIGNED
========================= */
    .student-info {
        margin: 30px 0 40px;
    }

    .info-row {
        margin: 10px 0;
        font-size: 12px;
    }

    .info-label {
        display: inline-block;
        width: 180px;
        font-weight: normal;
    }

    .info-value {
        display: inline-block;
        border-bottom: 1px solid #000;
        min-width: 350px;
        padding-left: 5px;
    }

    /* =========================
   CONTENT
========================= */
    .content {
        margin: 30px 0 40px;
        text-align: justify;
        line-height: 1.6;
        font-size: 12px;
    }

    /* =========================
   SIGNATURES - TWO COLUMN TABLE LAYOUT
========================= */
    .signatures {
        margin-top: 50px;
    }

    .signature-table {
        width: 100%;
        border-collapse: collapse;
    }

    .signature-table td {
        width: 50%;
        padding: 20px 30px;
        vertical-align: top;
        text-align: center;
    }

    .signature-area {
        height: 60px;
        margin-bottom: 5px;
        display: flex;
        align-items: flex-end;
        justify-content: center;
    }

    .signature-area img {
        max-height: 60px;
        max-width: 200px;
    }

    .sig-line {
        border-top: 1px solid #000;
        margin-top: 5px;
        padding-top: 5px;
        font-weight: bold;
        font-size: 12px;
        text-transform: uppercase;
    }

    .designation {
        font-size: 11px;
        margin-top: 2px;
    }

    .sig-date {
        font-size: 10px;
        margin-top: 3px;
    }

    /* =========================
   FOOTER
========================= */
    .footer {
        margin-top: 60px;
        text-align: center;
        font-size: 11px;
        line-height: 1.5;
    }
    </style>
</head>

<body>
    <div class="page">

        <div class="header">
            <p>Republic of the Philippines</p>
            <h3>BOHOL ISLAND STATE UNIVERSITY</h3>
            <p>Poblacion Norte, Clarin 6330, Bohol, Philippines</p>
            <p class="office-title">Office of the Student Development Services</p>
            <p style="font-style: italic; font-size: 10px;">Balance I Integrity I Stewardship I Uprightness</p>
            <br>
            <p class="office-title">Student Development Services Office</p>
            <p class="semester-info">
                <?= htmlspecialchars($template['semester']) ?> Semester A. Y.
                <?= htmlspecialchars($template['academic_year']) ?>
            </p>
            <h2>INTERNAL CLEARANCE</h2>
        </div>

        <div class="student-info">
            <div class="info-row">
                <span class="info-label">NAME OF STUDENT:</span>
                <span class="info-value"><?= htmlspecialchars($student['fullname']) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">COURSE, YEAR AND SECTION:</span>
                <span class="info-value">
                    <?= htmlspecialchars($student['course']) ?> -
                    <?= htmlspecialchars($student['year_level']) ?><?= htmlspecialchars($student['section']) ?>
                </span>
            </div>
        </div>

        <div class="content">
            <?= nl2br(htmlspecialchars($template['header_text'])) ?>
            <br><br>
            <?= nl2br(htmlspecialchars($clearance['description'])) ?>
        </div>

        <div class="signatures">
            <table class="signature-table">
                <?php
                $counter = 0;
                foreach ($faculty_list as $f):
                    if ($counter % 2 === 0) echo '<tr>';
                ?>
                <td>
                    <div class="signature-area">
                        <?php
if ($f['status'] === 'cleared' && !empty($f['signature_path'])) {
    $imagePath = $_SERVER['DOCUMENT_ROOT'] . '/Student Clearance System/uploads/signatures/' . basename($f['signature_path']);

    if (file_exists($imagePath)) {
        $type = pathinfo($imagePath, PATHINFO_EXTENSION);
        $data = file_get_contents($imagePath);
        $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
        echo '<img src="' . $base64 . '" alt="Signature">';
    }
}
?>

                    </div>

                    <?php if ($f['cleared_at']): ?>
                    <div class="sig-date"><?= date('m/d/Y', strtotime($f['cleared_at'])) ?></div>
                    <?php endif; ?>

                    <div class="sig-line"><?= strtoupper(htmlspecialchars($f['fullname'])) ?></div>
                    <div class="designation"><?= htmlspecialchars($f['designation']) ?></div>
                </td>
                <?php
                    if ($counter % 2 === 1) echo '</tr>';
                    $counter++;
                endforeach;
                if ($counter % 2 !== 0) echo '<td></td></tr>';
                ?>
            </table>
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