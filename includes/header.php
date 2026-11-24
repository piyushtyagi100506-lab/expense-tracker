<?php
/**
 * Global Header Component
 * 
 * Part of: Personal Expense Tracker Web Application
 * Web Application Development (WAD) Course Project
 * 
 * Explanatory Notes for Viva:
 * - HTML5 semantic layout with responsive viewport settings.
 * - Dynamic Dark / Light theme switching with localStorage and session persistence.
 * - Displays active currency symbol and session flash notifications.
 */

require_once __DIR__ . '/functions.php';

$pageTitle = $pageTitle ?? 'Dashboard';
$flash = get_flash();
$currentTheme = get_user_theme();
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= e($currentTheme) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?= e($pageTitle) ?> | ExpenseTracker</title>
    
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome Icons 6 CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

    <!-- Main Application Stylesheet -->
    <link rel="stylesheet" href="<?= e(app_url('assets/css/style.css')) ?>">
</head>
<body class="app-body">
    <div class="app-layout">
        <!-- Sidebar Navigation Component -->
        <?php include __DIR__ . '/sidebar.php'; ?>

        <!-- Main Content Area Wrapper -->
        <div class="main-wrapper">
            <!-- Top Navigation Bar -->
            <header class="topbar">
                <div class="topbar-left">
                    <button type="button" class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle navigation">
                        <i class="fa-solid fa-bars"></i>
                    </button>
                    <div class="page-header-title">
                        <h1><?= e($pageTitle) ?></h1>
                    </div>
                </div>

                <div class="topbar-right">
                    <!-- Quick Add Button -->
                    <a href="<?= e(app_url('pages/add_transaction.php')) ?>" class="btn btn-primary btn-sm">
                        <i class="fa-solid fa-plus"></i>
                        <span>Add Record</span>
                    </a>

                    <!-- Dark / Light Mode Toggle Button -->
                    <button type="button" class="theme-toggle-btn" id="themeToggleBtn" title="Toggle Dark/Light Mode" aria-label="Toggle Theme">
                        <i class="fa-solid <?= ($currentTheme === 'dark') ? 'fa-sun' : 'fa-moon' ?>" id="themeToggleIcon"></i>
                    </button>
                    
                    <!-- User Profile Pill -->
                    <a href="<?= e(app_url('pages/profile.php')) ?>" class="user-pill" title="User Settings & Profile">
                        <div class="user-avatar-small">
                            <?= strtoupper(substr(e(get_user_name()), 0, 1)) ?>
                        </div>
                        <span class="user-pill-name"><?= e(get_user_name()) ?></span>
                    </a>
                </div>
            </header>

            <!-- Page Body Content Area -->
            <main class="content-area">
                <!-- Flash Notification Banner -->
                <?php if ($flash): ?>
                    <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible" id="flashAlert">
                        <div class="alert-icon">
                            <?php if ($flash['type'] === 'success'): ?>
                                <i class="fa-solid fa-circle-check"></i>
                            <?php elseif ($flash['type'] === 'danger'): ?>
                                <i class="fa-solid fa-circle-exclamation"></i>
                            <?php elseif ($flash['type'] === 'warning'): ?>
                                <i class="fa-solid fa-triangle-exclamation"></i>
                            <?php else: ?>
                                <i class="fa-solid fa-circle-info"></i>
                            <?php endif; ?>
                        </div>
                        <div class="alert-text"><?= e($flash['message']) ?></div>
                        <button type="button" class="alert-close" onclick="this.parentElement.remove();">&times;</button>
                    </div>
                <?php endif; ?>
