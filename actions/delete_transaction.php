<?php
/**
 * Action Handler: Delete Transaction
 * 
 * Part of: Personal Expense Tracker Web Application
 * Web Application Development (WAD) Course Project
 * 
 * Explanatory Notes for Viva:
 * - Employs CSRF validation to protect against forced deletions via malicious links.
 * - Restricts DELETE execution with WHERE id = :id AND user_id = :user_id.
 * - Confirms that rowCount() is 1 before displaying success feedback.
 */

require_once __DIR__ . '/../includes/auth_check.php';

$userId = get_user_id();

// Capture ID and CSRF from POST or GET fallback
$id    = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
$token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';

if ($id <= 0) {
    set_flash('danger', 'Invalid transaction identifier provided.');
    redirect('pages/history.php');
}

// 1. Verify CSRF Token
if (!verify_csrf_token($token)) {
    set_flash('danger', 'Security validation failed (invalid CSRF token). Deletion canceled.');
    redirect('pages/history.php');
}

try {
    // 2. Perform safe deletion strictly scoped to the authenticated user
    $stmt = $pdo->prepare("DELETE FROM transactions WHERE id = :id AND user_id = :user_id");
    $stmt->execute([
        ':id'      => $id,
        ':user_id' => $userId
    ]);

    if ($stmt->rowCount() > 0) {
        set_flash('success', 'Transaction #' . $id . ' has been permanently deleted.');
    } else {
        set_flash('danger', 'Transaction not found or you do not have permission to delete it.');
    }

} catch (PDOException $e) {
    set_flash('danger', 'Database error while deleting record: ' . $e->getMessage());
}

redirect('pages/history.php');

