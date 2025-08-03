 <?php
// This must be the very first line of every page.
session_start();
include '../dbconnect.php'; // Path is '../' because we are one folder deep.

// Centralized security check. If the admin isn't logged in, redirect them.
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}

// --- CORRECT & FINAL VARIABLE DEFINITIONS ---
// These variables will now be available on any page that includes this header.
$current_admin_id   = $_SESSION['admin_id'];
$current_admin_name = $_SESSION['admin_name'] ?? 'Admin'; // Provide a default name
$current_admin_role = $_SESSION['admin_role'] ?? 'moderator'; // Provide a default role
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - TogetherA+</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --sidebar-bg: #2c3e50;
            --sidebar-hover: #34495e;
            --sidebar-text: #bdc3c7;
            --sidebar-text-active: #ffffff;
            --main-bg: #f4f7fc;
        }
        body { font-family: 'Nunito', sans-serif; background-color: var(--main-bg); }
        .sidebar {
            width: 260px; height: 100vh; background: var(--sidebar-bg); color: white;
            position: fixed; top: 0; left: 0; padding-top: 20px; z-index: 1000;
        }
        .sidebar-brand {
            padding: 0 20px; font-size: 24px; font-weight: 700; text-align: center;
            margin-bottom: 30px;
        }
        .sidebar-brand a { color: var(--sidebar-text-active); text-decoration: none; }
        .sidebar-nav { list-style: none; padding-left: 0; }
        .sidebar-nav li a {
            color: var(--sidebar-text); text-decoration: none; display: flex; align-items: center;
            padding: 12px 20px; transition: all 0.2s; font-weight: 600;
        }
        .sidebar-nav li.active a, .sidebar-nav li a:hover {
            background: var(--sidebar-hover); color: var(--sidebar-text-active);
        }
        .sidebar-nav li a .icon { margin-right: 15px; width: 20px; text-align: center; }
        .main-content { margin-left: 260px; }
        .top-navbar {
            background: #ffffff; padding: 15px 30px; display: flex; justify-content: flex-end;
            align-items: center; box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .content-area { padding: 30px; }
        .card { border: none; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-brand">
            <a href="index.php">TogetherA+</a>
        </div>
        <ul class="sidebar-nav">
            <?php $currentPage = basename($_SERVER['SCRIPT_NAME']); ?>
            <li class="<?php echo ($currentPage == 'index.php') ? 'active' : '' ?>">
                <a href="index.php"><i class="fas fa-tachometer-alt icon"></i> Dashboard</a>
            </li>
            <li class="<?php echo ($currentPage == 'manage_orders.php') ? 'active' : '' ?>">
                <a href="manage_orders.php"><i class="fas fa-shopping-cart icon"></i> Marketplace Orders</a>
            </li>
            <li class="<?php echo ($currentPage == 'manage_products.php') ? 'active' : '' ?>">
                <a href="manage_products.php"><i class="fas fa-box-open icon"></i> Manage Products</a>
            </li>

            <!-- NEWLY ADDED RESOURCE LINKS -->
            <li class="<?php echo ($currentPage == 'manage_resources.php') ? 'active' : '' ?>">
                <a href="manage_resources.php"><i class="fas fa-book-open-reader icon"></i> Manage Resources</a>
            </li>
            
            <!-- END OF NEW LINKS -->

            <li class="<?php echo ($currentPage == 'manage_users.php') ? 'active' : '' ?>">
                <a href="manage_users.php"><i class="fas fa-users icon"></i> Manage Users</a>
            </li>
            <li class="<?php echo ($currentPage == 'manage_helpers.php') ? 'active' : '' ?>">
                <a href="manage_helpers.php"><i class="fas fa-hands-helping icon"></i> Manage Helpers</a>
            </li>
            <li class="<?php echo ($currentPage == 'manage_tasks.php') ? 'active' : '' ?>">
                <a href="manage_tasks.php"><i class="fas fa-tasks icon"></i> Manage Tasks</a>
            </li>
            <li class="<?php echo ($currentPage == 'manage_reviews.php') ? 'active' : '' ?>">
                <a href="manage_reviews.php"><i class="fas fa-star icon"></i> Manage Reviews</a>
            </li>
            <?php if ($current_admin_role === 'super_admin'): ?>
            <li class="<?php echo ($currentPage == 'manage_admins.php') ? 'active' : '' ?>">
                <a href="manage_admins.php"><i class="fas fa-user-shield icon"></i> Manage Admins</a>
            </li>
            <?php endif; ?>
        </ul>
    </div>
    <div class="main-content">
        <nav class="top-navbar">
            <div class="dropdown">
                <a class="btn btn-light dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                    <i class="fas fa-user me-2"></i> <?php echo htmlspecialchars($current_admin_name); ?>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="admin_logout.php">Logout</a></li>
                </ul>
            </div>
        </nav>
        <main class="content-area">