<?php
/**
 * Action Handler: Save / Update Monthly Budget
 * 
 * Part of: Personal Expense Tracker Web Application
 * Web Application Development (WAD) Course Project
 * 
 * Explanatory Notes for Viva:
 * - Upserts budget target using MySQL ON DUPLICATE KEY UPDATE.
 * - Table has a UNIQUE KEY on (user_id, month, year) preventing duplicate rows.
 * - Enforces server-side range validation for months (1-12) and positive amounts.
 */

require_once __DIR__ . '/../includes/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('pages/budget.php');
}

// 1. CSRF Token Verification
if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    set_flash('danger', 'Security validation failed (invalid CSRF token). Budget not saved.');
    redirect('pages/budget.php');
}

$userId = get_user_id();
$month  = (int)($_POST['month'] ?? 0);
$year   = (int)($_POST['year'] ?? 0);
$amount = (float)($_POST['amount'] ?? 0);

$errors = [];

if ($month < 1 || $month > 12) {
    $errors[] = 'Please select a valid calendar month (1 to 12).';
}

if ($year < 2000 || $year > 2100) {
    $errors[] = 'Please select a valid year.';
}

if ($amount <= 0) {
    $errors[] = 'Budget limit must be a positive number greater than 0.';
}

if (!empty($errors)) {
    set_flash('danger', implode(' ', $errors));
    redirect('pages/budget.php');
}

try {
    // 2. Insert or Update on Duplicate Key
    $stmt = $pdo->prepare("
        INSERT INTO budgets (user_id, month, year, amount, created_at)
        VALUES (:user_id, :month, :year, :amount, NOW())
        ON DUPLICATE KEY UPDATE amount = VALUES(amount)
    ");
    $stmt->execute([
        ':user_id' => $userId,
        ':month'   => $month,
        ':year'    => $year,
        ':amount'  => $amount
    ]);

    $monthName = date('F', mktime(0, 0, 0, $month, 10));
    set_flash('success', "Budget limit of " . format_currency($amount) . " successfully saved for $monthName $year!");

} catch (PDOException $e) {
    set_flash('danger', 'Database error while saving budget: ' . $e->getMessage());
}

redirect('pages/budget.php');

