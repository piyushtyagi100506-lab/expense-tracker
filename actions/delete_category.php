<?php
/**
 * Action: Delete Custom Category
 * Prevents deletion if any transactions are linked to this category.
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
$id     = (int)($_POST['id'] ?? 0);

if ($id <= 0) {
    set_flash('danger', 'Invalid category ID.');
    redirect('pages/categories.php');
}

try {
    // 1. Verify category belongs to user
    $stmt = $pdo->prepare("SELECT id, name FROM categories WHERE id = :id AND user_id = :user_id LIMIT 1");
    $stmt->execute([':id' => $id, ':user_id' => $userId]);
    $cat = $stmt->fetch();

    if (!$cat) {
        set_flash('danger', 'Category not found or unauthorized.');
        redirect('pages/categories.php');
    }

    // 2. Check if any transactions reference this category
    $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM transactions WHERE category_id = :id AND user_id = :user_id");
    $stmt->execute([':id' => $id, ':user_id' => $userId]);
    $count = (int)$stmt->fetch()['total'];

    if ($count > 0) {
        set_flash('warning', "Cannot delete category '{$cat['name']}' because $count transaction(s) are currently categorized under it.");
        redirect('pages/categories.php');
    }

    // 3. Delete category
    $stmt = $pdo->prepare("DELETE FROM categories WHERE id = :id AND user_id = :user_id");
    $stmt->execute([':id' => $id, ':user_id' => $userId]);

    set_flash('success', "Custom category '{$cat['name']}' has been removed.");

} catch (PDOException $e) {
    set_flash('danger', 'Database error: ' . $e->getMessage());
}

redirect('pages/categories.php');
