<?php
/**
 * Action: Update User Profile & Preferences (Currency, Theme, Name)
 */

require_once __DIR__ . '/../includes/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('pages/profile.php');
}

if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    set_flash('danger', 'Security validation failed (invalid CSRF token).');
    redirect('pages/profile.php');
}

$userId   = get_user_id();
$name     = trim($_POST['name'] ?? '');
$email    = trim($_POST['email'] ?? '');
$currency = trim($_POST['currency'] ?? '$');
$theme    = in_array($_POST['theme'] ?? '', ['light', 'dark'], true) ? $_POST['theme'] : 'light';

$errors = [];

if (empty($name) || strlen($name) > 100) {
    $errors[] = 'Full name must be between 1 and 100 characters.';
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'A valid email address is required.';
}

// Check if email already used by someone else
if (empty($errors)) {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email AND id != :id LIMIT 1");
    $stmt->execute([':email' => $email, ':id' => $userId]);
    if ($stmt->fetch()) {
        $errors[] = 'That email address is already in use by another account.';
    }
}

if (!empty($errors)) {
    set_flash('danger', implode(' ', $errors));
    redirect('pages/profile.php');
}

try {
    $stmt = $pdo->prepare("
        UPDATE users 
        SET name = :name, 
            email = :email, 
            currency = :currency, 
            theme = :theme 
        WHERE id = :id
    ");
    $stmt->execute([
        ':name'     => $name,
        ':email'    => $email,
        ':currency' => $currency,
        ':theme'    => $theme,
        ':id'       => $userId
    ]);

    // Update active session states
    $_SESSION['user_name']     = $name;
    $_SESSION['user_email']    = $email;
    $_SESSION['user_currency'] = $currency;
    $_SESSION['user_theme']    = $theme;

    set_flash('success', 'Profile settings and currency preferences updated successfully!');

} catch (PDOException $e) {
    set_flash('danger', 'Database error: ' . $e->getMessage());
}

redirect('pages/profile.php');
