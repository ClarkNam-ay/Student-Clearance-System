<?php
require_once "../config/connection.php";

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
| GET CLEARANCE ID (DYNAMIC)
|--------------------------------------------------------------------------
*/
$clearance_id = isset($_GET['clearance_id']) ? (int)$_GET['clearance_id'] : 3;
if ($clearance_id <= 0) {
    die("Invalid clearance.");
}

/*
|--------------------------------------------------------------------------
| GET CLEARANCE INFO
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
| GET LATEST TEMPLATE
|--------------------------------------------------------------------------
*/
$templateStmt = $pdo->query("
    SELECT *
    FROM clearance_templates
    ORDER BY created_at DESC
    LIMIT 1
");
$template = $templateStmt->fetch();

if (!$template) {
    die("No clearance template found.");
}

/*
|--------------------------------------------------------------------------
| GET STUDENT INFO
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
| GET FACULTY + SIGNATURES
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
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Internal Clearance</title>

    <style>
    /* ==========================
   GLOBAL / PRINT SETTINGS
========================== */
    body {
        font-family: "Times New Roman", serif;
        color: #000;
        margin: 0;
    }

    .page {
        width: 8.27in;
        min-height: 11.69in;
        padding: 1in;
        margin: auto;
    }

    @media print {
        body {
            margin: 0;
        }

        .page {
            padding: 0.75in;
        }
    }

    /* ==========================
   HEADER
========================== */
    .header {
        text-align: center;
        line-height: 1.3;
    }

    .header h3 {
        margin: 5px 0;
        font-size: 18px;
    }

    .header h2 {
        margin: 20px 0 10px;
        text-decoration: underline;
        font-size: 20px;
    }

    /* ==========================
   STUDENT INFO
========================== */
    .student-info {
        margin-top: 30px;
        font-size: 15px;
    }

    .student-info p {
        margin: 10px 0;
    }

    .line {
        display: inline-block;
        border-bottom: 1px solid #000;
        min-width: 350px;
        padding-left: 5px;
    }

    /* ==========================
   CONTENT
========================== */
    .content {
        margin-top: 30px;
        text-align: justify;
        line-height: 1.6;
        font-size: 15px;
    }

    /* ==========================
   SIGNATURES
========================== */
    .signatures {
        margin-top: 40px;
        display: grid;
        grid-template-columns: 1fr 1fr;
        column-gap: 60px;
    }

    .signature-block {
        margin-top: 50px;
        text-align: center;
    }

    .signature-block img {
        height: 100px;
        object-fit: contain;
    }

    .sig-line {
        border-top: 1px solid #000;
        margin-top: 8px;
        font-weight: bold;
    }

    .designation {
        font-size: 13px;
    }

    .sig-date {
        font-size: 11px;
        margin-top: 2px;
    }

    /* ==========================
   FOOTER
========================== */
    .footer {
        margin-top: 60px;
        text-align: center;
        font-size: 12px;
    }
    </style>
</head>

<body>

    <div class="page">

        <!-- HEADER -->
        <div class="header">
            <p>Republic of the Philippines</p>
            <h3>BOHOL ISLAND STATE UNIVERSITY</h3>
            <p>Clarin Campus, Clarin, Bohol</p>
            <p><strong>Student Development Services Office</strong></p>
            <p><?= htmlspecialchars($template['semester']) ?> Semester A.Y.
                <?= htmlspecialchars($template['academic_year']) ?></p>

            <h2>INTERNAL CLEARANCE</h2>
        </div>

        <!-- STUDENT INFO -->
        <div class="student-info">
            <p>
                <strong>Name of Student:</strong>
                <span class="line"><?= htmlspecialchars($student['fullname']) ?></span>
            </p>
            <p>
                <strong>Course, Year & Section:</strong>
                <span class="line">
                    <?= htmlspecialchars($student['course']) ?>
                    <?= htmlspecialchars($student['year_level']) ?><?= htmlspecialchars($student['section']) ?>
                </span>
            </p>
        </div>

        <!-- BODY -->
        <div class="content">
            <?= nl2br(htmlspecialchars($template['header_text'])) ?>
            <br><br>
            <?= nl2br(htmlspecialchars($clearance['description'])) ?>
        </div>

        <!-- SIGNATURES -->
        <div class="signatures">
            <?php foreach ($faculty_list as $f): ?>
            <div class="signature-block">

                <?php if ($f['status'] === 'cleared' && !empty($f['signature_path'])): ?>
                <img src="/Student Clearance System/uploads/signatures/<?= basename($f['signature_path']) ?>">
                <?php else: ?>
                <div style="height:50px;"></div>
                <?php endif; ?>

                <div class="sig-line">
                    <?= htmlspecialchars($f['fullname']) ?>
                </div>

                <div class="designation">
                    <?= htmlspecialchars($f['designation']) ?>
                </div>

                <?php if ($f['status'] === 'cleared' && $f['cleared_at']): ?>
                <div class="sig-date">
                    <?= date('m/d/Y', strtotime($f['cleared_at'])) ?>
                </div>
                <?php endif; ?>

            </div>
            <?php endforeach; ?>
        </div>

        <!-- FOOTER -->
        <div class="footer">
            <?= nl2br(htmlspecialchars($template['footer_text'])) ?>
        </div>

    </div>

</body>

</html>