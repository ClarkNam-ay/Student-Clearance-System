<?php
// create_admin.php - ONE TIME USE. DELETE AFTER RUNNING
require './config/connection.php'; // must define $pdo

// ========= CONFIG =========
$admin_email    = 'admin123@gmail.com';
$admin_fullname = 'System Administrator';
$admin_password = 'admin123@'; // CHANGE AFTER FIRST LOGIN
// ==========================

try {
    // check if user already exists
    $stmt = $pdo->prepare("SELECT id, role FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$admin_email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && $user['role'] === 'admin') {
        echo "Admin already exists. Nothing to do.\n";
        exit;
    }

    if ($user && $user['role'] !== 'admin') {
        // promote existing user
        $stmt = $pdo->prepare("UPDATE users SET role = 'admin' WHERE id = ?");
        $stmt->execute([$user['id']]);
        echo "Existing user promoted to ADMIN.\n";
        exit;
    }

    // create new admin
    $hash = password_hash($admin_password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare(
        "INSERT INTO users (fullname, email, password, role)
         VALUES (?, ?, ?, 'admin')"
    );
    $stmt->execute([$admin_fullname, $admin_email, $hash]);

    echo "Admin account created successfully.\n";
    echo "Email: {$admin_email}\n";
    echo "IMPORTANT: Delete this file and change the password immediately.\n";

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}