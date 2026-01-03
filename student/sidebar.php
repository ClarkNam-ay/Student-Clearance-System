<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

.sidebar {
    position: fixed;
    left: 0;
    top: 0;
    width: 260px;
    height: 100vh;
    background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
    box-shadow: 4px 0 24px rgba(0, 0, 0, 0.12);
    overflow: hidden;
    z-index: 1000;
    transition: all 0.3s ease;
    display: flex;
    flex-direction: column;
}

.logo {
    padding: 30px 24px;
    font-family: 'Inter', sans-serif;
    font-size: 22px;
    font-weight: 700;
    color: white;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    display: flex;
    align-items: center;
    gap: 12px;
    background: rgba(255, 255, 255, 0.05);
}

.logo i {
    font-size: 24px;
    color: #667eea;
}

.menu {
    list-style: none;
    padding: 20px 0;
    flex: 1;
    overflow-y: auto;
}

.menu::-webkit-scrollbar {
    width: 6px;
}

.menu::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.05);
}

.menu::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.2);
    border-radius: 10px;
}

.menu::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.3);
}

.menu-title {
    padding: 20px 24px 10px;
    font-family: 'Inter', sans-serif;
    font-size: 11px;
    font-weight: 600;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-top: 8px;
}

.menu li:first-child {
    margin-top: 0;
}

.menu li a {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 24px;
    font-family: 'Inter', sans-serif;
    font-size: 14px;
    font-weight: 500;
    color: #cbd5e1;
    text-decoration: none;
    transition: all 0.3s ease;
    position: relative;
    border-left: 3px solid transparent;
}

.menu li a:hover {
    background: rgba(255, 255, 255, 0.08);
    color: white;
    border-left-color: #667eea;
    padding-left: 28px;
}

.menu li a.active {
    background: linear-gradient(90deg, rgba(102, 126, 234, 0.2) 0%, transparent 100%);
    color: white;
    border-left-color: #667eea;
    font-weight: 600;
}

.menu li a i {
    font-size: 16px;
    width: 20px;
    text-align: center;
    color: #94a3b8;
    transition: color 0.3s ease;
}

.menu li a:hover i,
.menu li a.active i {
    color: #667eea;
}

.menu li a.logout {
    color: #fca5a5;
    margin-top: 12px;
}

.menu li a.logout:hover {
    background: rgba(239, 68, 68, 0.1);
    border-left-color: #ef4444;
    color: #fee2e2;
}

.menu li a.logout i {
    color: #fca5a5;
}

.menu li a.logout:hover i {
    color: #fee2e2;
}

/* Badge for notifications (optional enhancement) */
.badge {
    margin-left: auto;
    background: #667eea;
    color: white;
    font-size: 10px;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 12px;
    min-width: 20px;
    text-align: center;
}

/* User Profile Section (optional enhancement) */
.sidebar-footer {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 20px 24px;
    background: rgba(0, 0, 0, 0.2);
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.user-profile {
    display: flex;
    align-items: center;
    gap: 12px;
}

.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
    font-size: 16px;
    font-family: 'Inter', sans-serif;
}

.user-info h4 {
    font-family: 'Inter', sans-serif;
    font-size: 14px;
    font-weight: 600;
    color: white;
    margin-bottom: 2px;
}

.user-info p {
    font-family: 'Inter', sans-serif;
    font-size: 12px;
    color: #94a3b8;
}

/* Mobile Toggle Button */
.sidebar-toggle {
    display: none;
    position: fixed;
    top: 20px;
    left: 20px;
    z-index: 1001;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    color: white;
    width: 44px;
    height: 44px;
    border-radius: 12px;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
    transition: all 0.3s ease;
}

.sidebar-toggle:hover {
    transform: scale(1.05);
}

.sidebar-toggle i {
    font-size: 18px;
}

/* Responsive */
@media (max-width: 1024px) {
    .sidebar {
        transform: translateX(-100%);
    }

    .sidebar.active {
        transform: translateX(0);
    }

    .sidebar-toggle {
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        z-index: 999;
    }

    .overlay.active {
        display: block;
    }
}
</style>

<!-- Mobile Toggle Button -->
<button class="sidebar-toggle" onclick="toggleSidebar()">
    <i class="fas fa-bars"></i>
</button>

<!-- Overlay for mobile -->
<div class="overlay" onclick="toggleSidebar()"></div>

<aside class="sidebar">
    <h2 class="logo">
        <i class="fas fa-shield-alt"></i>
        Student Panel
    </h2>

    <ul class="menu">
        <li><a href="dashboard.php" class="active">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a></li>

        <li class="menu-title">Clearance</li>
        <!-- <li><a href="create_user.php">
                <i class="fas fa-user-plus"></i>
                <span>Create User</span>
            </a></li> -->
        <li><a href="manage_user.php">
                <i class="fas fa-users-cog"></i>
                <span>Clearance Form</span>
            </a></li>

        <li class="menu-title">Clearance</li>
        <li><a href="create_clearance.php">
                <i class="fas fa-clipboard-check"></i>
                <span>Create Clearance</span>
            </a></li>
        <li><a href="#">
                <i class="fas fa-check-double"></i>
                <span>Approved Clearances</span>
            </a></li>

        <li class="menu-title">System</li>
        <li><a href="#">
                <i class="fas fa-cog"></i>
                <span>Settings</span>
            </a></li>
        <li><a href="../logout.php" class="logout">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a></li>
    </ul>

    <!-- Optional: User Profile Footer -->
    <!-- <div class="sidebar-footer">
        <div class="user-profile">
            <div class="user-avatar">A</div>
            <div class="user-info">
                <h4>Admin User</h4>
                <p>Administrator</p>
            </div>
        </div>
    </div> -->
</aside>

<script>
// Highlight active menu item based on current page
document.addEventListener('DOMContentLoaded', function() {
    const currentPage = window.location.pathname.split('/').pop();
    const menuLinks = document.querySelectorAll('.menu a');

    menuLinks.forEach(link => {
        link.classList.remove('active');
        const href = link.getAttribute('href');
        if (href && href.includes(currentPage)) {
            link.classList.add('active');
        }
    });
});

// Toggle sidebar on mobile
function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.querySelector('.overlay');
    sidebar.classList.toggle('active');
    overlay.classList.toggle('active');
}

// Close sidebar when clicking on a link (mobile)
document.querySelectorAll('.sidebar a').forEach(link => {
    link.addEventListener('click', function() {
        if (window.innerWidth <= 1024) {
            toggleSidebar();
        }
    });
});
</script>