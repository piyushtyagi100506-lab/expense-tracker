<?php
/**
 * Action: Post Recurring Bill to Transaction Ledger
 * 1. Inserts a new transaction into transactions table.
 * 2. Advances next_due_date according to frequency (+1 week, +1 month, +1 year).
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
    set_flash('danger', 'Invalid recurring item.');
    redirect('pages/recurring.php');
}

try {
    // 1. Fetch recurring item ensuring ownership
    $stmt = $pdo->prepare("SELECT * FROM recurring_transactions WHERE id = :id AND user_id = :user_id LIMIT 1");
    $stmt->execute([':id' => $id, ':user_id' => $userId]);
    $rec = $stmt->fetch();

    if (!$rec) {
        set_flash('danger', 'Recurring item not found.');
        redirect('pages/recurring.php');
    }

    // 2. Insert transaction into main ledger
    $stmt = $pdo->prepare("
        INSERT INTO transactions (user_id, category_id, type, amount, description, transaction_date, created_at)
        VALUES (:user_id, :category_id, :type, :amount, :description, CURRENT_DATE(), NOW())
    ");
    $stmt->execute([
        ':user_id'     => $userId,
        ':category_id' => $rec['category_id'],
        ':type'        => $rec['type'],
        ':amount'      => $rec['amount'],
        ':description' => 'Recurring: ' . $rec['title']
    ]);

    // 3. Calculate and advance next due date
    $currentDue = strtotime($rec['next_due_date']);
    if ($rec['frequency'] === 'weekly') {
        $nextDate = date('Y-m-d', strtotime('+1 week', $currentDue));
    } elseif ($rec['frequency'] === 'yearly') {
        $nextDate = date('Y-m-d', strtotime('+1 year', $currentDue));
    } else {
        // default monthly
        $nextDate = date('Y-m-d', strtotime('+1 month', $currentDue));
    }

    $stmt = $pdo->prepare("
        UPDATE recurring_transactions 
        SET next_due_date = :next_due_date 
        WHERE id = :id AND user_id = :user_id
    ");
    $stmt->execute([
        ':next_due_date' => $nextDate,
        ':id'            => $id,
        ':user_id'       => $userId
    ]);

    set_flash('success', "Posted " . format_currency($rec['amount']) . " for '{$rec['title']}' to your transactions ledger! Next due date advanced to " . format_date($nextDate) . ".");

} catch (PDOException $e) {
    set_flash('danger', 'Database error: ' . $e->getMessage());
}

redirect('pages/recurring.php');
