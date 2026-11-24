<?php
/**
 * Action: Save New Recurring Commitment / Subscription
 */

require_once __DIR__ . '/../includes/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('pages/recurring.php');
}

if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    set_flash('danger', 'Security validation failed (invalid CSRF token).');
    redirect('pages/recurring.php');
}

$userId      = get_user_id();
$title       = trim($_POST['title'] ?? '');
$amount      = (float)($_POST['amount'] ?? 0);
$type        = trim($_POST['type'] ?? 'expense');
$categoryId  = (int)($_POST['category_id'] ?? 0);
$frequency   = trim($_POST['frequency'] ?? 'monthly');
$nextDueDate = trim($_POST['next_due_date'] ?? date('Y-m-d'));

$errors = [];

if (empty($title) || strlen($title) > 120) {
    $errors[] = 'Title is required and must be under 120 characters.';
}

if ($amount <= 0) {
    $errors[] = 'Amount must be greater than 0.';
}

if (!in_array($type, ['income', 'expense'], true)) {
    $errors[] = 'Invalid transaction type.';
}

if (!in_array($frequency, ['weekly', 'monthly', 'yearly'], true)) {
    $errors[] = 'Invalid recurrence frequency.';
}

if ($categoryId <= 0) {
    $errors[] = 'Please select a category.';
}

if (!empty($errors)) {
    set_flash('danger', implode(' ', $errors));
    redirect('pages/recurring.php');
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO recurring_transactions (user_id, category_id, type, title, amount, frequency, next_due_date, status, created_at)
        VALUES (:user_id, :category_id, :type, :title, :amount, :frequency, :next_due_date, 'active', NOW())
    ");
    $stmt->execute([
        ':user_id'       => $userId,
        ':category_id'   => $categoryId,
        ':type'          => $type,
        ':title'         => $title,
        ':amount'        => $amount,
        ':frequency'     => $frequency,
        ':next_due_date' => $nextDueDate
    ]);

    set_flash('success', "Recurring item '$title' (" . format_currency($amount) . "/$frequency) has been configured!");

} catch (PDOException $e) {
    set_flash('danger', 'Database error: ' . $e->getMessage());
}

redirect('pages/recurring.php');
