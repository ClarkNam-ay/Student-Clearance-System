<?php
require_once "../config/connection.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Get admin info
$admin_name = $_SESSION['fullname'] ?? 'Admin User';
$admin_email = $_SESSION['email'] ?? 'admin@system.com';
$admin_initials = strtoupper(substr($admin_name, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Clearance System</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script defer src="../assets/js/admin.js"></script>

    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Inter', sans-serif;
        background: #f8fafc;
    }

    .top-header {
        position: fixed;
        top: 0;
        left: 260px;
        right: 0;
        height: 70px;
        background: white;
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
        z-index: 998;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 30px;
        transition: left 0.3s ease;
    }

    .header-left {
        display: flex;
        align-items: center;
        gap: 20px;
    }

    .breadcrumb {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
        color: #64748b;
    }

    .breadcrumb i {
        font-size: 12px;
    }

    .breadcrumb a {
        color: #64748b;
        text-decoration: none;
        transition: color 0.2s ease;
    }

    .breadcrumb a:hover {
        color: #667eea;
    }

    .breadcrumb .current {
        color: #1e293b;
        font-weight: 600;
    }

    .search-bar {
        display: flex;
        align-items: center;
        gap: 12px;
        background: #f8fafc;
        padding: 10px 16px;
        border-radius: 10px;
        border: 2px solid transparent;
        transition: all 0.3s ease;
        width: 300px;
    }

    .search-bar:focus-within {
        background: white;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .search-bar i {
        color: #94a3b8;
        font-size: 14px;
    }

    .search-bar input {
        border: none;
        background: transparent;
        outline: none;
        font-family: 'Inter', sans-serif;
        font-size: 14px;
        width: 100%;
        color: #1e293b;
    }

    .search-bar input::placeholder {
        color: #94a3b8;
    }

    .header-right {
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .header-icon {
        position: relative;
        width: 40px;
        height: 40px;
        border-radius: 10px;
        background: #f8fafc;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s ease;
        color: #64748b;
    }

    .header-icon:hover {
        background: #667eea;
        color: white;
        transform: translateY(-2px);
    }

    .header-icon .badge {
        position: absolute;
        top: -4px;
        right: -4px;
        background: #ef4444;
        color: white;
        font-size: 10px;
        font-weight: 700;
        padding: 2px 6px;
        border-radius: 10px;
        min-width: 18px;
        text-align: center;
    }

    .divider {
        width: 1px;
        height: 30px;
        background: #e2e8f0;
    }

    .user-menu {
        position: relative;
    }

    .user-trigger {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 6px 12px 6px 6px;
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.2s ease;
        background: #f8fafc;
    }

    .user-trigger:hover {
        background: #e2e8f0;
    }

    .user-avatar {
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
    }

    .user-details h4 {
        font-size: 14px;
        font-weight: 600;
        color: #1e293b;
        line-height: 1.2;
    }

    .user-details p {
        font-size: 12px;
        color: #64748b;
    }

    .user-trigger i {
        color: #94a3b8;
        font-size: 12px;
        transition: transform 0.2s ease;
    }

    .user-menu.active .user-trigger i {
        transform: rotate(180deg);
    }

    .dropdown {
        position: absolute;
        top: calc(100% + 10px);
        right: 0;
        background: white;
        border-radius: 12px;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
        min-width: 220px;
        opacity: 0;
        visibility: hidden;
        transform: translateY(-10px);
        transition: all 0.3s ease;
        overflow: hidden;
    }

    .user-menu.active .dropdown {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }

    .dropdown-header {
        padding: 16px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }

    .dropdown-header h4 {
        font-size: 14px;
        font-weight: 600;
        margin-bottom: 2px;
    }

    .dropdown-header p {
        font-size: 12px;
        opacity: 0.9;
    }

    .dropdown-menu {
        padding: 8px;
    }

    .dropdown-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px;
        border-radius: 8px;
        text-decoration: none;
        color: #334155;
        font-size: 14px;
        transition: all 0.2s ease;
    }

    .dropdown-item:hover {
        background: #f8fafc;
        color: #667eea;
    }

    .dropdown-item i {
        width: 20px;
        text-align: center;
        color: #94a3b8;
    }

    .dropdown-item:hover i {
        color: #667eea;
    }

    .dropdown-divider {
        height: 1px;
        background: #e2e8f0;
        margin: 8px 0;
    }

    .dropdown-item.logout {
        color: #ef4444;
    }

    .dropdown-item.logout:hover {
        background: #fef2f2;
    }

    .dropdown-item.logout i {
        color: #ef4444;
    }

    /* Notification Dropdown */
    .notification-dropdown {
        position: absolute;
        top: calc(100% + 10px);
        right: 0;
        background: white;
        border-radius: 12px;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
        width: 360px;
        opacity: 0;
        visibility: hidden;
        transform: translateY(-10px);
        transition: all 0.3s ease;
        max-height: 480px;
        display: flex;
        flex-direction: column;
    }

    .header-icon.notifications.active .notification-dropdown {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }

    .notification-header {
        padding: 16px 20px;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .notification-header h4 {
        font-size: 16px;
        font-weight: 600;
        color: #1e293b;
    }

    .mark-read {
        font-size: 12px;
        color: #667eea;
        cursor: pointer;
        transition: color 0.2s ease;
    }

    .mark-read:hover {
        color: #764ba2;
    }

    .notification-list {
        overflow-y: auto;
        max-height: 400px;
    }

    .notification-item {
        padding: 16px 20px;
        border-bottom: 1px solid #f1f5f9;
        cursor: pointer;
        transition: background 0.2s ease;
    }

    .notification-item:hover {
        background: #f8fafc;
    }

    .notification-item.unread {
        background: #f0f4ff;
    }

    .notification-item h5 {
        font-size: 14px;
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 4px;
    }

    .notification-item p {
        font-size: 13px;
        color: #64748b;
        margin-bottom: 6px;
    }

    .notification-item span {
        font-size: 11px;
        color: #94a3b8;
    }

    /* Mobile responsive */
    @media (max-width: 1024px) {
        .top-header {
            left: 0;
        }

        .search-bar {
            display: none;
        }

        .breadcrumb {
            display: none;
        }
    }

    @media (max-width: 768px) {
        .top-header {
            padding: 0 15px;
        }

        .user-details {
            display: none;
        }

        .notification-dropdown {
            width: 320px;
            right: -100px;
        }
    }

    /* Page content adjustment */
    .main-content {
        margin-top: 70px;
    }
    </style>
</head>

<body>
    <header class="top-header">
        <div class="header-left">
            <div class="breadcrumb">
                <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                <i class="fas fa-chevron-right"></i>
                <span class="current" id="breadcrumb-current"></span>
            </div>
        </div>

        <div class="header-right">
            <!-- Search Bar -->
            <div class="search-bar">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Search users, clearances...">
            </div>

            <!-- Notifications -->
            <div class="header-icon notifications" onclick="toggleNotifications()">
                <i class="fas fa-bell"></i>
                <span class="badge">3</span>

                <div class="notification-dropdown">
                    <div class="notification-header">
                        <h4>Notifications</h4>
                        <span class="mark-read">Mark all as read</span>
                    </div>
                    <div class="notification-list">
                        <div class="notification-item unread">
                            <h5>New Clearance Request</h5>
                            <p>John Doe submitted a clearance request</p>
                            <span>5 minutes ago</span>
                        </div>
                        <div class="notification-item unread">
                            <h5>Faculty Registration</h5>
                            <p>New faculty member registered in the system</p>
                            <span>1 hour ago</span>
                        </div>
                        <div class="notification-item unread">
                            <h5>System Update</h5>
                            <p>System maintenance scheduled for tonight</p>
                            <span>3 hours ago</span>
                        </div>
                        <div class="notification-item">
                            <h5>Clearance Approved</h5>
                            <p>Jane Smith's clearance has been approved</p>
                            <span>Yesterday</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Messages -->
            <div class="header-icon">
                <i class="fas fa-envelope"></i>
                <span class="badge">2</span>
            </div>

            <div class="divider"></div>

            <!-- User Menu -->
            <div class="user-menu" onclick="toggleUserMenu()">
                <div class="user-trigger">
                    <div class="user-avatar"><?= $admin_initials ?></div>
                    <div class="user-details">
                        <h4><?= htmlspecialchars($admin_name) ?></h4>
                        <p>Administrator</p>
                    </div>
                    <i class="fas fa-chevron-down"></i>
                </div>

                <div class="dropdown">
                    <div class="dropdown-header">
                        <h4><?= htmlspecialchars($admin_name) ?></h4>
                        <p><?= htmlspecialchars($admin_email) ?></p>
                    </div>
                    <div class="dropdown-menu">
                        <a href="profile.php" class="dropdown-item">
                            <i class="fas fa-user"></i>
                            <span>My Profile</span>
                        </a>
                        <a href="settings.php" class="dropdown-item">
                            <i class="fas fa-cog"></i>
                            <span>Settings</span>
                        </a>
                        <a href="help.php" class="dropdown-item">
                            <i class="fas fa-question-circle"></i>
                            <span>Help & Support</span>
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="../logout.php" class="dropdown-item logout">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Logout</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <script>
    // Toggle user menu
    function toggleUserMenu() {
        const userMenu = document.querySelector('.user-menu');
        userMenu.classList.toggle('active');

        // Close notifications if open
        document.querySelector('.notifications')?.classList.remove('active');
    }

    // Toggle notifications
    function toggleNotifications() {
        const notifications = document.querySelector('.notifications');
        notifications.classList.toggle('active');

        // Close user menu if open
        document.querySelector('.user-menu')?.classList.remove('active');
    }

    // Close dropdowns when clicking outside
    document.addEventListener('click', function(event) {
        if (!event.target.closest('.user-menu')) {
            document.querySelector('.user-menu')?.classList.remove('active');
        }
        if (!event.target.closest('.notifications')) {
            document.querySelector('.notifications')?.classList.remove('active');
        }
    });

    // Update breadcrumb based on current page
    document.addEventListener('DOMContentLoaded', function() {
        const currentPage = window.location.pathname.split('/').pop();
        const breadcrumbCurrent = document.getElementById('breadcrumb-current');

        const pageNames = {
            // 'dashboard.php': 'Dashboard',
            'manage_user.php': 'Manage Users',
            'create_user.php': 'Create User',
            'settings.php': 'Settings'
        };

        if (pageNames[currentPage]) {
            breadcrumbCurrent.textContent = pageNames[currentPage];
        }
    });
    </script>