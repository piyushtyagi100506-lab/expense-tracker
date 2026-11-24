<?php
/**
 * Action Handler: Save New Transaction
 * 
 * Part of: Personal Expense Tracker Web Application
 * Web Application Development (WAD) Course Project
 * 
 * Explanatory Notes for Viva:
 * - Validates request authenticity via CSRF token verification.
 * - Performs server-side validation on type, positive numerical amount, and valid date.
 * - Validates that the selected category actually belongs to system defaults or the authenticated user.
 * - Uses a prepared statement with parameter binding to prevent SQL injection.
 */

require_once __DIR__ . '/../includes/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('pages/dashboard.php');
}

// 1. Verify CSRF Token
if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    set_flash('danger', 'Invalid security token (CSRF). Transaction was not saved.');
    redirect('pages/add_transaction.php');
}

$userId          = get_user_id();
$type            = trim($_POST['type'] ?? '');
$amount          = (float)($_POST['amount'] ?? 0);
$categoryId      = (int)($_POST['category_id'] ?? 0);
$transactionDate = trim($_POST['transaction_date'] ?? '');
$description     = trim($_POST['description'] ?? '');

$errors = [];

// 2. Server-Side Validations
if (!in_array($type, ['income', 'expense'], true)) {
    $errors[] = 'Invalid transaction type selected.';
}

if ($amount <= 0) {
    $errors[] = 'Transaction amount must be a positive number greater than 0.';
}

if ($categoryId <= 0) {
    $errors[] = 'Please select a valid category.';
} else {
    // Verify category existence and ownership
    $stmt = $pdo->prepare("
        SELECT id FROM categories 
        WHERE id = :cat_id AND (user_id IS NULL OR user_id = :user_id)
        LIMIT 1
    ");
    $stmt->execute([
        ':cat_id'  => $categoryId,
        ':user_id' => $userId
    ]);
    if (!$stmt->fetch()) {
        $errors[] = 'The selected category does not exist or is unauthorized.';
    }
}

// Validate date format YYYY-MM-DD
if (empty($transactionDate) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $transactionDate)) {
    $errors[] = 'Please provide a valid transaction date.';
}

// Truncate description to column length
if (strlen($description) > 255) {
    $description = substr($description, 0, 255);
}

// 3. Handle Errors or Save Record
if (!empty($errors)) {
    set_flash('danger', implode(' ', $errors));
    redirect('pages/add_transaction.php?type=' . urlencode($type));
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO transactions (user_id, category_id, type, amount, description, transaction_date, created_at)
        VALUES (:user_id, :category_id, :type, :amount, :description, :transaction_date, NOW())
    ");
    $stmt->execute([
        ':user_id'          => $userId,
        ':category_id'      => $categoryId,
        ':type'             => $type,
        ':amount'           => $amount,
        ':description'      => !empty($description) ? $description : null,
        ':transaction_date' => $transactionDate
    ]);

    set_flash('success', ucfirst($type) . ' transaction of ' . format_currency($amount) . ' has been recorded!');
    redirect('pages/history.php');

} catch (PDOException $e) {
    set_flash('danger', 'Database error while saving transaction: ' . $e->getMessage());
    redirect('pages/add_transaction.php');
}

