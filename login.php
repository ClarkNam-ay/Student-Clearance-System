<?php
require_once "config/connection.php"; // PDO + session + CSRF

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // CSRF check
    if (!csrf_validate()) {
        die('Invalid CSRF token.');
    }

    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    if ($email === '' || $password === '') {
        $error = "All fields are required.";
    } else {

        $stmt = $pdo->prepare(
            "SELECT id, fullname, password, role, status 
             FROM users 
             WHERE email = ? 
             LIMIT 1"
        );
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && $user['status'] === 'active' &&
            password_verify($password, $user['password'])) {

            // Login success
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['fullname'] = $user['fullname'];
            $_SESSION['role']     = $user['role'];

            // Role-based redirect
            switch ($user['role']) {
                case 'admin':
                    header("Location: admin/dashboard.php");
                    break;
                case 'faculty':
                    header("Location: faculty/dashboard.php");
                    break;
                case 'student':
                    header("Location: student/dashboard.php");
                    break;
                default:
                    die("Invalid user role.");
            }
            exit;

        } else {
            $error = "Invalid email or password.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Student Clearance System | Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="assets/css/loginstyle.css">
    <!-- <link rel="shortcut icon" href="images/heartlogo.png"> -->

    <style>
    .container {
        width: 100%;
        height: 100vh;
        background-image:
            linear-gradient(rgba(0, 0, 50, .6), rgba(0, 0, 50, .6)),
            url(images/front.jpg);
        background-position: center;
        background-size: cover;
        display: flex;
        justify-content: center;
        align-items: center;
    }
    </style>
</head>

<body>

    <div class="container">
        <div class="login-box">

            <!-- <div class="logo">
                <img src="images/login.png" alt="Logo">
            </div> -->

            <h1>Student Clearance System</h1>
            <h3>Login</h3>

            <?php if ($error): ?>
            <p style="color:red; text-align:center;">
                <?= htmlspecialchars($error) ?>
            </p>
            <?php endif; ?>

            <form method="POST" action="">
                <?= csrf_input() ?>

                <div class="input-field">
                    <input type="email" name="email" required>
                    <label>Email</label>
                </div>

                <div class="input-field">
                    <input type="password" name="password" required>
                    <label>Password</label>
                </div>

                <button type="submit" class="login-button">Login</button>
            </form>

        </div>
    </div>

</body>

</html>