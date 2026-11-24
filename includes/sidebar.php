<?php
/**
 * Global Sidebar Navigation Component
 * 
 * Part of: Personal Expense Tracker Web Application
 * Web Application Development (WAD) Course Project
 * 
 * Explanatory Notes for Viva:
 * - Dynamic active link state based on $activePage variable.
 * - Expanded menu featuring Categories, Savings Goals, and Recurring Bills.
 * - Mobile responsive drawer with toggle overlay.
 */

$activePage = $activePage ?? '';
?>
<!-- Sidebar Overlay for mobile screen backdrop -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<aside class="sidebar" id="appSidebar">
    <!-- Brand / Logo Area -->
    <div class="sidebar-brand">
        <a href="<?= e(app_url('pages/dashboard.php')) ?>" class="brand-link">
            <div class="brand-icon">
                <i class="fa-solid fa-wallet"></i>
            </div>
            <div class="brand-text">
                <span class="brand-title">ExpenseTracker</span>
                <span class="brand-subtitle">WAD Course System</span>
            </div>
        </a>
        <button type="button" class="sidebar-close-btn" id="sidebarClose" aria-label="Close menu">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>

    <!-- Navigation Menu Items -->
    <nav class="sidebar-nav">
        <div class="nav-section-label">FINANCES</div>
        <ul class="nav-list">
            <li class="nav-item">
                <a href="<?= e(app_url('pages/dashboard.php')) ?>" class="nav-link <?= ($activePage === 'dashboard') ? 'active' : '' ?>">
                    <i class="fa-solid fa-gauge-high nav-icon"></i>
                    <span class="nav-label">Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= e(app_url('pages/add_transaction.php')) ?>" class="nav-link <?= ($activePage === 'add_transaction') ? 'active' : '' ?>">
                    <i class="fa-solid fa-circle-plus nav-icon"></i>
                    <span class="nav-label">Add Transaction</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= e(app_url('pages/history.php')) ?>" class="nav-link <?= ($activePage === 'history') ? 'active' : '' ?>">
                    <i class="fa-solid fa-receipt nav-icon"></i>
                    <span class="nav-label">Transaction History</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= e(app_url('pages/budget.php')) ?>" class="nav-link <?= ($activePage === 'budget') ? 'active' : '' ?>">
                    <i class="fa-solid fa-bullseye nav-icon"></i>
                    <span class="nav-label">Monthly Budget</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= e(app_url('pages/reports.php')) ?>" class="nav-link <?= ($activePage === 'reports') ? 'active' : '' ?>">
                    <i class="fa-solid fa-chart-pie nav-icon"></i>
                    <span class="nav-label">Expense Reports</span>
                </a>
            </li>
        </ul>

        <div class="nav-section-label">MANAGEMENT</div>
        <ul class="nav-list">
            <li class="nav-item">
                <a href="<?= e(app_url('pages/goals.php')) ?>" class="nav-link <?= ($activePage === 'goals') ? 'active' : '' ?>">
                    <i class="fa-solid fa-piggy-bank nav-icon"></i>
                    <span class="nav-label">Savings Goals</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= e(app_url('pages/recurring.php')) ?>" class="nav-link <?= ($activePage === 'recurring') ? 'active' : '' ?>">
                    <i class="fa-solid fa-repeat nav-icon"></i>
                    <span class="nav-label">Recurring Bills</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= e(app_url('pages/categories.php')) ?>" class="nav-link <?= ($activePage === 'categories') ? 'active' : '' ?>">
                    <i class="fa-solid fa-tags nav-icon"></i>
                    <span class="nav-label">Categories</span>
                </a>
            </li>
        </ul>

        <div class="nav-section-label">ACCOUNT</div>
        <ul class="nav-list">
            <li class="nav-item">
                <a href="<?= e(app_url('pages/profile.php')) ?>" class="nav-link <?= ($activePage === 'profile') ? 'active' : '' ?>">
                    <i class="fa-solid fa-gear nav-icon"></i>
                    <span class="nav-label">Settings &amp; Profile</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= e(app_url('auth/logout.php')) ?>" class="nav-link logout-link">
                    <i class="fa-solid fa-arrow-right-from-bracket nav-icon"></i>
                    <span class="nav-label">Sign Out</span>
                </a>
            </li>
        </ul>
    </nav>

    <!-- Sidebar Bottom User Profile Card -->
    <div class="sidebar-user">
        <div class="user-avatar">
            <?= strtoupper(substr(e(get_user_name()), 0, 1)) ?>
        </div>
        <div class="user-meta">
            <div class="user-name" title="<?= e(get_user_name()) ?>"><?= e(get_user_name()) ?></div>
            <div class="user-email" title="<?= e(get_user_email()) ?>"><?= e(get_user_email()) ?></div>
        </div>
        <a href="<?= e(app_url('pages/profile.php')) ?>" class="user-logout-btn" title="Settings">
            <i class="fa-solid fa-gear"></i>
        </a>
    </div>
</aside>
