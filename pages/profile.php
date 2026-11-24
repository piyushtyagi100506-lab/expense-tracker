<?php
/**
 * User Profile & Preferences View & Controller
 * 
 * Part of: Personal Expense Tracker Web Application
 * Web Application Development (WAD) Course Project
 * 
 * Explanatory Notes for Viva:
 * - Allows personalized customization: Currency Symbol ($, ₹, €, £, etc.) and Light/Dark Theme.
 * - Password modification with verification of existing credentials before updating.
 * - Updates session states immediately so UI changes reflect in real-time.
 */

require_once __DIR__ . '/../includes/auth_check.php';

$pageTitle = 'Settings & Profile';
$activePage = 'profile';
$userId = get_user_id();

// Fetch current user details
$stmt = $pdo->prepare("SELECT id, name, email, currency, theme, created_at FROM users WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $userId]);
$user = $stmt->fetch();

$availableCurrencies = [
    '$'  => '$ (USD - US Dollar / Default)',
    '₹'  => '₹ (INR - Indian Rupee)',
    '€'  => '€ (EUR - Euro)',
    '£'  => '£ (GBP - British Pound)',
    '¥'  => '¥ (JPY - Japanese Yen)',
    'C$' => 'C$ (CAD - Canadian Dollar)',
    'A$' => 'A$ (AUD - Australian Dollar)',
    'AED'=> 'AED (United Arab Emirates Dirham)'
];

include __DIR__ . '/../includes/header.php';
?>

<div class="profile-page">
    <div class="page-intro-header">
        <h2>Settings &amp; Preferences</h2>
        <p class="text-muted">Manage your profile, preferred currency, visual theme, and security credentials.</p>
    </div>

    <div class="grid-2-col">
        <!-- 1. General Profile & Currency Preferences -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fa-solid fa-user-gear text-teal"></i>
                    Profile &amp; Display Settings
                </h3>
            </div>
            <div class="card-body">
                <form action="<?= e(app_url('actions/update_profile.php')) ?>" method="POST" novalidate>
                    <?= csrf_field() ?>

                    <div class="form-group">
                        <label for="userName" class="form-label">Full Name <span class="text-danger">*</span></label>
                        <div class="input-icon-wrap">
                            <i class="fa-regular fa-user input-icon"></i>
                            <input type="text" name="name" id="userName" class="form-control" 
                                   value="<?= e($user['name']) ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="userEmail" class="form-label">Email Address <span class="text-danger">*</span></label>
                        <div class="input-icon-wrap">
                            <i class="fa-regular fa-envelope input-icon"></i>
                            <input type="email" name="email" id="userEmail" class="form-control" 
                                   value="<?= e($user['email']) ?>" required>
                        </div>
                    </div>

                    <!-- Currency Selection -->
                    <div class="form-group">
                        <label for="userCurrency" class="form-label">Preferred Currency Symbol <span class="text-danger">*</span></label>
                        <select name="currency" id="userCurrency" class="form-control" required>
                            <?php foreach ($availableCurrencies as $sym => $label): ?>
                                <option value="<?= e($sym) ?>" <?= ($user['currency'] === $sym) ? 'selected' : '' ?>>
                                    <?= e($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <span class="field-hint">All dashboard metrics, tables, and reports will render using this symbol.</span>
                    </div>

                    <!-- Theme Selection -->
                    <div class="form-group">
                        <label for="userTheme" class="form-label">Visual Theme Preference</label>
                        <select name="theme" id="userTheme" class="form-control">
                            <option value="light" <?= ($user['theme'] === 'light') ? 'selected' : '' ?>>Light Theme (Standard)</option>
                            <option value="dark" <?= ($user['theme'] === 'dark') ? 'selected' : '' ?>>Dark Theme (Night Mode)</option>
                        </select>
                    </div>

                    <div class="form-actions mt-3">
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fa-solid fa-floppy-disk"></i> Save Preferences
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 2. Security & Password Change -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fa-solid fa-shield-halved text-teal"></i>
                    Change Password
                </h3>
            </div>
            <div class="card-body">
                <form action="<?= e(app_url('actions/change_password.php')) ?>" method="POST" novalidate>
                    <?= csrf_field() ?>

                    <div class="form-group">
                        <label for="currentPassword" class="form-label">Current Password <span class="text-danger">*</span></label>
                        <div class="input-icon-wrap">
                            <i class="fa-solid fa-lock input-icon"></i>
                            <input type="password" name="current_password" id="currentPassword" class="form-control" 
                                   placeholder="Enter your current password" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="newPassword" class="form-label">New Password <span class="text-danger">*</span></label>
                        <div class="input-icon-wrap">
                            <i class="fa-solid fa-key input-icon"></i>
                            <input type="password" name="new_password" id="newPassword" class="form-control" 
                                   placeholder="Minimum 6 characters" minlength="6" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="confirmNewPassword" class="form-label">Confirm New Password <span class="text-danger">*</span></label>
                        <div class="input-icon-wrap">
                            <i class="fa-solid fa-check-double input-icon"></i>
                            <input type="password" name="confirm_new_password" id="confirmNewPassword" class="form-control" 
                                   placeholder="Re-type new password" required>
                        </div>
                    </div>

                    <div class="form-actions mt-3">
                        <button type="submit" class="btn btn-secondary btn-block">
                            <i class="fa-solid fa-lock"></i> Update Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Account Metadata Overview -->
    <div class="card mt-4">
        <div class="card-body">
            <div class="detail-list">
                <div class="detail-row">
                    <span class="detail-label">Account Member Since:</span>
                    <span class="detail-value"><?= e(format_date($user['created_at'], 'F d, Y h:i A')) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">User Account ID:</span>
                    <span class="detail-value">#<?= (int)$user['id'] ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Security Protocol:</span>
                    <span class="detail-value text-success font-medium">BCrypt Cost 10 / Session Token Protected</span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
