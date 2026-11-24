<?php
/**
 * Action Handler: Update Existing Transaction
 * 
 * Part of: Personal Expense Tracker Web Application
 * Web Application Development (WAD) Course Project
 * 
 * Explanatory Notes for Viva:
 * - Implements strict authorization: WHERE clause requires both id AND user_id to match.
 * - Prevents tampering with transactions belonging to other users.
 * - Full input validation and CSRF verification.
 */

require_once __DIR__ . '/../includes/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('pages/history.php');
}

$id = (int)($_POST['id'] ?? 0);

// 1. Verify CSRF Token
if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    set_flash('danger', 'Security validation failed (invalid CSRF token). Update aborted.');
    redirect('pages/edit_transaction.php?id=' . $id);
}

$userId          = get_user_id();
$type            = trim($_POST['type'] ?? '');
$amount          = (float)($_POST['amount'] ?? 0);
$categoryId      = (int)($_POST['category_id'] ?? 0);
$transactionDate = trim($_POST['transaction_date'] ?? '');
$description     = trim($_POST['description'] ?? '');

// 2. Verify Record Ownership First (Prevents IDOR)
$stmt = $pdo->prepare("SELECT id FROM transactions WHERE id = :id AND user_id = :user_id LIMIT 1");
$stmt->execute([':id' => $id, ':user_id' => $userId]);
if (!$stmt->fetch()) {
    set_flash('danger', 'Unauthorized operation. Record does not belong to your account.');
    redirect('pages/history.php');
}

$errors = [];

// 3. Server-Side Validations
if (!in_array($type, ['income', 'expense'], true)) {
    $errors[] = 'Invalid transaction type.';
}

if ($amount <= 0) {
    $errors[] = 'Amount must be greater than 0.';
}

if ($categoryId <= 0) {
    $errors[] = 'Please select a valid category.';
} else {
    $stmt = $pdo->prepare("
        SELECT id FROM categories 
        WHERE id = :cat_id AND (user_id IS NULL OR user_id = :user_id)
        LIMIT 1
    ");
    $stmt->execute([':cat_id' => $categoryId, ':user_id' => $userId]);
    if (!$stmt->fetch()) {
        $errors[] = 'Selected category is not valid or not accessible.';
    }
}

if (empty($transactionDate) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $transactionDate)) {
    $errors[] = 'Please provide a valid transaction date.';
}

if (strlen($description) > 255) {
    $description = substr($description, 0, 255);
}

if (!empty($errors)) {
    set_flash('danger', implode(' ', $errors));
    redirect('pages/edit_transaction.php?id=' . $id);
}

try {
    $stmt = $pdo->prepare("
        UPDATE transactions 
        SET category_id      = :category_id,
            type             = :type,
            amount           = :amount,
            description      = :description,
            transaction_date = :transaction_date
        WHERE id = :id AND user_id = :user_id
    ");
    $stmt->execute([
        ':category_id'      => $categoryId,
        ':type'             => $type,
        ':amount'           => $amount,
        ':description'      => !empty($description) ? $description : null,
        ':transaction_date' => $transactionDate,
        ':id'               => $id,
        ':user_id'          => $userId
    ]);

    set_flash('success', 'Transaction #' . $id . ' has been updated successfully!');
    redirect('pages/history.php');

} catch (PDOException $e) {
    set_flash('danger', 'Database error: ' . $e->getMessage());
    redirect('pages/edit_transaction.php?id=' . $id);
}

