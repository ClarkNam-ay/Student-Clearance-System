<?php
require_once "../config/connection.php";

/* ===== ADMIN PROTECTION ===== */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

/* ===== VARIABLES ===== */
$error = "";
$success = "";

/* ===== FORM SUBMISSION ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // CSRF protection
    if (!csrf_validate()) {
        die("Invalid CSRF token.");
    }

    $fullname = trim($_POST['fullname']);
    $course   = trim($_POST['course']);
    $year_level = trim($_POST['year_level']);
    $section  = trim($_POST['section']);
    $email    = trim($_POST['email']);
    $password = $_POST['password'];
    $role     = $_POST['role']; // student or faculty

    // BASIC VALIDATION
    if ($fullname === "" || $email === "" || $password === "" || $role === "") {
        $error = "All fields are required.";
    }
    elseif (!in_array($role, ['student', 'faculty'])) {
        $error = "Invalid role selected.";
    }
    else {

        // CHECK IF EMAIL EXISTS
        $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$email]);

        if ($check->fetch()) {
            $error = "Email already exists.";
        }
        else {
            // CREATE ACCOUNT
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare(
                "INSERT INTO users (fullname, course, year_level, section, email, password, role)
                 VALUES (?, ?, ?, ?, ?, ?, ?)"
            );

            if ($stmt->execute([$fullname, $course, $year_level, $section, $email, $hashedPassword, $role])) {
                $success = ucfirst($role) . " account created successfully.";
            } else {
                $error = "Failed to create account.";
            }
        }
    }
}
?>
<?php include "header.php"; ?>
<?php include "sidebar.php"; ?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Create User | Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    .main-content {
        margin-left: 260px;
        padding: 30px;
        min-height: 100vh;
        background: #f8fafc;
    }

    .box {
        width: 400px;
        background: #fff;
        margin: 80px auto;
        padding: 25px;
        border-radius: 10px;
    }

    input,
    select,
    button {
        width: 100%;
        padding: 10px;
        margin-top: 10px;
    }

    button {
        background: #1e293b;
        color: #fff;
        border: none;
        cursor: pointer;
    }

    button:hover {
        background: #334155;
    }
    </style>
</head>

<body>
    <div class="main-content">
        <div class="box">
            <h2>Create Student / Faculty</h2>

            <?php if ($error): ?>
            <p style="color:red;"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>

            <?php if ($success): ?>
            <p style="color:green;"><?= htmlspecialchars($success) ?></p>
            <?php endif; ?>

            <form method="POST">
                <?= csrf_input() ?>

                <input type="text" name="fullname" placeholder="Full Name" required>
                <input type="text" name="course" placeholder="Course" required>
                <input type="text" name="year_level" placeholder="Year Level" required>
                <input type="text" name="section" placeholder="Section" required>

                <input type="email" name="email" placeholder="Email" required>

                <input type="password" name="password" placeholder="Password" required>

                <select name="role" required>
                    <option value="">-- Select Role --</option>
                    <option value="student">Student</option>
                    <option value="faculty">Faculty</option>
                </select>

                <button type="submit">Create Account</button>
            </form>
        </div>
    </div>
</body>

</html>

<?php include "footer.php"; ?>