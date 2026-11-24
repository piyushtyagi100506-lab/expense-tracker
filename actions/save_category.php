<?php
/**
 * Action: Save Custom Category
 */

require_once __DIR__ . '/../includes/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('pages/categories.php');
}

if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    set_flash('danger', 'Security validation failed (invalid CSRF token).');
    redirect('pages/categories.php');
}

$userId = get_user_id();
$name   = trim($_POST['name'] ?? '');
$type   = trim($_POST['type'] ?? '');

if (empty($name) || strlen($name) > 60) {
    set_flash('danger', 'Category name must be between 1 and 60 characters.');
    redirect('pages/categories.php');
}

if (!in_array($type, ['income', 'expense'], true)) {
    set_flash('danger', 'Invalid category type selected.');
    redirect('pages/categories.php');
}

try {
    // Check if category name already exists for this user or in system
    $stmt = $pdo->prepare("
        SELECT id FROM categories 
        WHERE LOWER(name) = LOWER(:name) AND (user_id IS NULL OR user_id = :user_id) AND type = :type
        LIMIT 1
    ");
    $stmt->execute([
        ':name'    => $name,
        ':user_id' => $userId,
        ':type'    => $type
    ]);
    if ($stmt->fetch()) {
        set_flash('warning', 'A category with this name already exists.');
        redirect('pages/categories.php');
    }

    $stmt = $pdo->prepare("
        INSERT INTO categories (user_id, name, type, created_at)
        VALUES (:user_id, :name, :type, NOW())
    ");
    $stmt->execute([
        ':user_id' => $userId,
        ':name'    => $name,
        ':type'    => $type
    ]);

    set_flash('success', "Custom category '$name' successfully created!");

} catch (PDOException $e) {
    set_flash('danger', 'Database error: ' . $e->getMessage());
}

redirect('pages/categories.php');
