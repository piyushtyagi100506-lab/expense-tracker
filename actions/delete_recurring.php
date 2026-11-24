<?php
/**
 * Action: Delete Recurring Item
 */

require_once __DIR__ . '/../includes/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('pages/recurring.php');
}

if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    set_flash('danger', 'Security validation failed (invalid CSRF token).');
    redirect('pages/recurring.php');
}

$userId = get_user_id();
$id     = (int)($_POST['id'] ?? 0);

if ($id <= 0) {
    set_flash('danger', 'Invalid ID.');
    redirect('pages/recurring.php');
}

try {
    $stmt = $pdo->prepare("DELETE FROM recurring_transactions WHERE id = :id AND user_id = :user_id");
    $stmt->execute([':id' => $id, ':user_id' => $userId]);

    if ($stmt->rowCount() > 0) {
        set_flash('success', 'Recurring subscription removed.');
    } else {
        set_flash('danger', 'Item not found or unauthorized.');
    }
} catch (PDOException $e) {
    set_flash('danger', 'Database error: ' . $e->getMessage());
}

redirect('pages/recurring.php');
