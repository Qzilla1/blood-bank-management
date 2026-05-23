<?php
/**
 * Global Header Layout
 * Restricts unauthenticated access and establishes the dark sidebar grid system
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/auth.php';

// Secure the page - redirects to login.php if not authenticated
check_login();

// Get the basename of the current active page
$activePage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lifeline Bank - Blood Management Console</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome 6 CDN -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Premium Custom Stylesheet -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="app-wrapper">
    <!-- Dark Sidebar Panel -->
    <aside class="sidebar-panel" id="sidebarPanel">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <i class="fa-solid fa-droplet"></i>
            </div>
            <h2 class="sidebar-brand-name">Lifeline Bank</h2>
            <button type="button" class="sidebar-close-btn" id="sidebarClose">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <ul class="sidebar-menu">
            <li>
                <a href="index.php" class="menu-item-link">
                    <i class="fa-solid fa-chart-line"></i>
                    <span>Dashboard Overview</span>
                </a>
            </li>
            <li>
                <a href="donors.php" class="menu-item-link">
                    <i class="fa-solid fa-users"></i>
                    <span>Donor Directory</span>
                </a>
            </li>
            <li>
                <a href="requests.php" class="menu-item-link">
                    <i class="fa-solid fa-hand-holding-droplet"></i>
                    <span>Blood Requests</span>
                </a>
            </li>
            <li>
                <a href="inventory.php" class="menu-item-link">
                    <i class="fa-solid fa-boxes-stacked"></i>
                    <span>Stock Levels</span>
                </a>
            </li>
            <li class="mt-auto">
                <a href="logout.php" class="menu-item-link text-danger-hover">
                    <i class="fa-solid fa-right-from-bracket text-danger"></i>
                    <span class="text-danger">Log Out</span>
                </a>
            </li>
        </ul>

        <div class="sidebar-footer">
            <div class="admin-avatar">
                <i class="fa-solid fa-user-shield text-muted"></i>
            </div>
            <div class="admin-details">
                <span class="admin-name"><?php echo htmlspecialchars(get_admin_fullname()); ?></span>
                <span class="admin-role">System Admin</span>
            </div>
        </div>
    </aside>

    <!-- Main Workspace Container -->
    <div class="content-wrapper">
        
        <!-- Top Navigation Bar -->
        <header class="top-nav-bar">
            <!-- Sidebar toggle button for mobile/tablet resolutions -->
            <button class="sidebar-toggle-btn" id="sidebarToggle">
                <i class="fa-solid fa-bars"></i>
            </button>

            <!-- Dynamic Breadcrumbs / Title Info block based on page context -->
            <div class="page-title-block d-none d-sm-block">
                <?php if ($activePage === 'index.php'): ?>
                    <h1>Operational Control Room</h1>
                    <p>Consolidated statistics, inventory, and activity logs</p>
                <?php elseif ($activePage === 'donors.php' || $activePage === 'donor-add.php' || $activePage === 'donor-edit.php'): ?>
                    <h1>Donor Directory Management</h1>
                    <p>Register, update, and search voluntary blood donor profiles</p>
                <?php elseif ($activePage === 'requests.php' || $activePage === 'request-add.php' || $activePage === 'request-edit.php'): ?>
                    <h1>Emergency Request Processing</h1>
                    <p>Manage, validate, and fulfill urgent hospital blood requests</p>
                <?php elseif ($activePage === 'inventory.php'): ?>
                    <h1>Stock Inventory Levels</h1>
                    <p>Visual statistics and operational adjustments of blood units</p>
                <?php endif; ?>
            </div>

            <!-- Header Quick Utilities -->
            <div class="ms-auto d-flex align-items-center gap-3">
                <span class="badge bg-success-subtle text-success px-3 py-2 border border-success-subtle rounded-pill font-monospace d-none d-md-inline-block">
                    <i class="fa-regular fa-clock me-1"></i> Live Session Connected
                </span>
                <div class="dropdown">
                    <button class="btn btn-premium-secondary border-0 dropdown-toggle px-3 py-2 rounded-3" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fa-regular fa-circle-user me-2"></i> <?php echo htmlspecialchars(get_admin_username()); ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark border-secondary p-1 rounded-3">
                        <li><a class="dropdown-item rounded-2 py-2" href="logout.php"><i class="fa-solid fa-right-from-bracket me-2 text-danger"></i>Log Out</a></li>
                    </ul>
                </div>
            </div>
        </header>

        <!-- Main Body Content Injection Point -->
