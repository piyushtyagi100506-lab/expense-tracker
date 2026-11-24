<?php
/**
 * Action: Create New Savings Goal
 */

require_once __DIR__ . '/../includes/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('pages/goals.php');
}

if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    set_flash('danger', 'Security validation failed (invalid CSRF token).');
    redirect('pages/goals.php');
}

$userId       = get_user_id();
$title        = trim($_POST['title'] ?? '');
$targetAmount = (float)($_POST['target_amount'] ?? 0);
$savedAmount  = max(0.00, (float)($_POST['saved_amount'] ?? 0));
$targetDate   = !empty($_POST['target_date']) ? trim($_POST['target_date']) : null;
$notes        = trim($_POST['notes'] ?? '');

$errors = [];

if (empty($title) || strlen($title) > 120) {
    $errors[] = 'Goal title is required and cannot exceed 120 characters.';
}

if ($targetAmount <= 0) {
    $errors[] = 'Target amount must be a positive number greater than 0.';
}

if (!empty($errors)) {
    set_flash('danger', implode(' ', $errors));
    redirect('pages/goals.php');
}

$status = ($savedAmount >= $targetAmount) ? 'completed' : 'active';

try {
    $stmt = $pdo->prepare("
        INSERT INTO savings_goals (user_id, title, target_amount, saved_amount, target_date, notes, status, created_at)
        VALUES (:user_id, :title, :target_amount, :saved_amount, :target_date, :notes, :status, NOW())
    ");
    $stmt->execute([
        ':user_id'       => $userId,
        ':title'         => $title,
        ':target_amount' => $targetAmount,
        ':saved_amount'  => $savedAmount,
        ':target_date'   => $targetDate,
        ':notes'         => !empty($notes) ? $notes : null,
        ':status'        => $status
    ]);

    set_flash('success', "Savings goal '$title' of " . format_currency($targetAmount) . " has been created!");

} catch (PDOException $e) {
    set_flash('danger', 'Database error: ' . $e->getMessage());
}

redirect('pages/goals.php');
