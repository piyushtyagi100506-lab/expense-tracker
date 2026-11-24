<?php
/**
 * Edit Transaction Controller & View
 * 
 * Part of: Personal Expense Tracker Web Application
 * Web Application Development (WAD) Course Project
 * 
 * Explanatory Notes for Viva:
 * - Strict Ownership Verification: Validates that the transaction belongs to the currently logged-in user.
 * - Prevents Insecure Direct Object Reference (IDOR) attacks: A user cannot modify another user's records by tampering with the URL ID parameter.
 * - Pre-populates all input fields for intuitive user editing.
 */

require_once __DIR__ . '/../includes/auth_check.php';

$pageTitle = 'Edit Transaction';
$activePage = 'history';
$userId = get_user_id();

$transactionId = (int)($_GET['id'] ?? 0);

if ($transactionId <= 0) {
    set_flash('danger', 'Invalid transaction identifier provided.');
    redirect('pages/history.php');
}

// Fetch transaction ensuring user ownership
$stmt = $pdo->prepare("
    SELECT * FROM transactions 
    WHERE id = :id AND user_id = :user_id 
    LIMIT 1
");
$stmt->execute([
    ':id'      => $transactionId,
    ':user_id' => $userId
]);
$tx = $stmt->fetch();

if (!$tx) {
    set_flash('danger', 'Transaction not found or you do not have permission to view/edit it.');
    redirect('pages/history.php');
}

// Fetch all system and user categories
$categories = get_categories($pdo, null, $userId);

include __DIR__ . '/../includes/header.php';
?>

<div class="form-page-container">
    <div class="card form-card">
        <div class="card-header flex-between">
            <div>
                <h3 class="card-title">
                    <i class="fa-regular fa-pen-to-square text-teal"></i>
                    Edit Transaction #<?= e($tx['id']) ?>
                </h3>
                <p class="card-subtitle">Update transaction details and save changes to your ledger.</p>
            </div>
            <a href="<?= e(app_url('pages/history.php')) ?>" class="btn btn-outline-secondary btn-sm">
                <i class="fa-solid fa-arrow-left"></i> Back to History
            </a>
        </div>

        <div class="card-body">
            <form action="<?= e(app_url('actions/update_transaction.php')) ?>" method="POST" id="transactionForm" novalidate>
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int)$tx['id'] ?>">

                <!-- 1. Transaction Type Toggle Selector -->
                <div class="form-group">
                    <label class="form-label">Transaction Type <span class="text-danger">*</span></label>
                    <div class="type-toggle-group">
                        <label class="type-toggle-option <?= ($tx['type'] === 'expense') ? 'active-expense' : '' ?>" id="labelTypeExpense">
                            <input type="radio" name="type" value="expense" id="typeExpense" 
                                   <?= ($tx['type'] === 'expense') ? 'checked' : '' ?>>
                            <span class="icon"><i class="fa-solid fa-arrow-up"></i></span>
                            <span class="text">Expense (Money Out)</span>
                        </label>

                        <label class="type-toggle-option <?= ($tx['type'] === 'income') ? 'active-income' : '' ?>" id="labelTypeIncome">
                            <input type="radio" name="type" value="income" id="typeIncome" 
                                   <?= ($tx['type'] === 'income') ? 'checked' : '' ?>>
                            <span class="icon"><i class="fa-solid fa-arrow-down"></i></span>
                            <span class="text">Income (Money In)</span>
                        </label>
                    </div>
                    <div class="invalid-feedback" id="typeError"></div>
                </div>

                <div class="form-row">
                    <!-- 2. Transaction Amount -->
                    <div class="form-col">
                        <div class="form-group">
                            <label for="amount" class="form-label">Amount (<?= CURRENCY_SYMBOL ?>) <span class="text-danger">*</span></label>
                            <div class="input-icon-wrap">
                                <span class="currency-prefix"><?= CURRENCY_SYMBOL ?></span>
                                <input type="number" step="0.01" min="0.01" id="amount" name="amount" 
                                       class="form-control" value="<?= e($tx['amount']) ?>" required>
                            </div>
                            <div class="invalid-feedback" id="amountError"></div>
                        </div>
                    </div>

                    <!-- 3. Transaction Date -->
                    <div class="form-col">
                        <div class="form-group">
                            <label for="transaction_date" class="form-label">Date of Transaction <span class="text-danger">*</span></label>
                            <div class="input-icon-wrap">
                                <i class="fa-regular fa-calendar input-icon"></i>
                                <input type="date" id="transaction_date" name="transaction_date" 
                                       class="form-control" value="<?= e($tx['transaction_date']) ?>" required>
                            </div>
                            <div class="invalid-feedback" id="dateError"></div>
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <!-- 4. Category Dropdown -->
                    <div class="form-col">
                        <div class="form-group">
                            <label for="category_id" class="form-label">Category <span class="text-danger">*</span></label>
                            <div class="input-icon-wrap">
                                <i class="fa-solid fa-tags input-icon"></i>
                                <select id="category_id" name="category_id" class="form-control" required>
                                    <option value="">-- Select a Category --</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= (int)$cat['id'] ?>" 
                                                data-type="<?= e($cat['type']) ?>"
                                                <?= ($cat['id'] == $tx['category_id']) ? 'selected' : '' ?>>
                                            <?= e($cat['name']) ?> (<?= ucfirst(e($cat['type'])) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="invalid-feedback" id="categoryError"></div>
                        </div>
                    </div>

                    <!-- 5. Description / Note -->
                    <div class="form-col">
                        <div class="form-group">
                            <label for="description" class="form-label">Description / Note</label>
                            <div class="input-icon-wrap">
                                <i class="fa-regular fa-comment-dots input-icon"></i>
                                <input type="text" id="description" name="description" class="form-control" 
                                       value="<?= e($tx['description']) ?>" maxlength="255">
                            </div>
                            <div class="invalid-feedback" id="descriptionError"></div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary btn-lg" id="submitTransactionBtn">
                        <i class="fa-solid fa-floppy-disk"></i> Update Transaction
                    </button>
                    <a href="<?= e(app_url('pages/history.php')) ?>" class="btn btn-secondary btn-lg">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

