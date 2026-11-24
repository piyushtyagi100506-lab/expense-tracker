<?php
/**
 * Action: Delete Savings Goal
 */

require_once __DIR__ . '/../includes/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('pages/goals.php');
}

if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    set_flash('danger', 'Security validation failed (invalid CSRF token).');
    redirect('pages/goals.php');
}

$userId = get_user_id();
$id     = (int)($_POST['id'] ?? 0);

if ($id <= 0) {
    set_flash('danger', 'Invalid goal ID.');
    redirect('pages/goals.php');
}

try {
    $stmt = $pdo->prepare("DELETE FROM savings_goals WHERE id = :id AND user_id = :user_id");
    $stmt->execute([':id' => $id, ':user_id' => $userId]);

    if ($stmt->rowCount() > 0) {
        set_flash('success', 'Savings goal deleted.');
    } else {
        set_flash('danger', 'Goal not found or unauthorized.');
    }
} catch (PDOException $e) {
    set_flash('danger', 'Database error: ' . $e->getMessage());
}

redirect('pages/goals.php');
