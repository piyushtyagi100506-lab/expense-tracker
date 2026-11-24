<?php
/**
 * Action: Deposit Funds toward a Savings Goal
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
$goalId = (int)($_POST['goal_id'] ?? 0);
$amount = (float)($_POST['amount'] ?? 0);

if ($goalId <= 0 || $amount <= 0) {
    set_flash('danger', 'Please enter a valid deposit amount greater than 0.');
    redirect('pages/goals.php');
}

try {
    // 1. Verify goal belongs to user
    $stmt = $pdo->prepare("SELECT * FROM savings_goals WHERE id = :id AND user_id = :user_id LIMIT 1");
    $stmt->execute([':id' => $goalId, ':user_id' => $userId]);
    $goal = $stmt->fetch();

    if (!$goal) {
        set_flash('danger', 'Savings goal not found or access denied.');
        redirect('pages/goals.php');
    }

    $newSavedAmount = (float)$goal['saved_amount'] + $amount;
    $newStatus = ($newSavedAmount >= (float)$goal['target_amount']) ? 'completed' : 'active';

    // 2. Update goal record
    $stmt = $pdo->prepare("
        UPDATE savings_goals 
        SET saved_amount = :saved_amount,
            status = :status 
        WHERE id = :id AND user_id = :user_id
    ");
    $stmt->execute([
        ':saved_amount' => $newSavedAmount,
        ':status'       => $newStatus,
        ':id'           => $goalId,
        ':user_id'      => $userId
    ]);

    $congrats = ($newStatus === 'completed') ? ' 🎉 Congratulations! You reached your target!' : '';
    set_flash('success', "Deposited " . format_currency($amount) . " into '{$goal['title']}'! Total saved: " . format_currency($newSavedAmount) . "." . $congrats);

} catch (PDOException $e) {
    set_flash('danger', 'Database error: ' . $e->getMessage());
}

redirect('pages/goals.php');
