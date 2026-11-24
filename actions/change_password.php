<?php
/**
 * Action: Change User Password
 */

require_once __DIR__ . '/../includes/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('pages/profile.php');
}

if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    set_flash('danger', 'Security validation failed (invalid CSRF token).');
    redirect('pages/profile.php');
}

$userId             = get_user_id();
$currentPassword    = $_POST['current_password'] ?? '';
$newPassword        = $_POST['new_password'] ?? '';
$confirmNewPassword = $_POST['confirm_new_password'] ?? '';

$errors = [];

if (empty($currentPassword)) {
    $errors[] = 'Please enter your current password.';
}

if (empty($newPassword) || strlen($newPassword) < 6) {
    $errors[] = 'New password must be at least 6 characters long.';
}

if ($newPassword !== $confirmNewPassword) {
    $errors[] = 'New password confirmation does not match.';
}

if (!empty($errors)) {
    set_flash('danger', implode(' ', $errors));
    redirect('pages/profile.php');
}

try {
    // 1. Fetch current hash
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $userId]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($currentPassword, $user['password'])) {
        set_flash('danger', 'Current password was incorrect. Password was not changed.');
        redirect('pages/profile.php');
    }

    // 2. Hash new password and update
    $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("UPDATE users SET password = :password WHERE id = :id");
    $stmt->execute([':password' => $newHash, ':id' => $userId]);

    set_flash('success', 'Your password has been changed successfully!');

} catch (PDOException $e) {
    set_flash('danger', 'Database error: ' . $e->getMessage());
}

redirect('pages/profile.php');
