<?php
/**
 * Add Income / Expense Transaction View & Form
 * 
 * Part of: Personal Expense Tracker Web Application
 * Web Application Development (WAD) Course Project
 * 
 * Explanatory Notes for Viva:
 * - Dual-purpose form for both Income and Expense transactions.
 * - Dynamic category switching via JavaScript according to selected transaction type.
 * - Double-layer validation: Client-side JS validation + Server-side validation in actions/save_transaction.php.
 * - Includes CSRF token to prevent Cross-Site Request Forgery attacks.
 */

require_once __DIR__ . '/../includes/auth_check.php';

$pageTitle = 'Add Transaction';
$activePage = 'add_transaction';
$userId = get_user_id();

// Initial preset type from query parameter or default to 'expense'
$defaultType = isset($_GET['type']) && in_array($_GET['type'], ['income', 'expense'], true) 
               ? $_GET['type'] 
               : 'expense';

// Fetch all system and user categories
$categories = get_categories($pdo, null, $userId);

include __DIR__ . '/../includes/header.php';
?>

<div class="form-page-container">
    <div class="card form-card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fa-solid fa-circle-plus text-teal"></i>
                Record New Transaction
            </h3>
            <p class="card-subtitle">Add your income deposits or expense purchases to keep your accounts updated.</p>
        </div>

        <div class="card-body">
            <form action="<?= e(app_url('actions/save_transaction.php')) ?>" method="POST" id="transactionForm" novalidate>
                <?= csrf_field() ?>

                <!-- 1. Transaction Type Toggle Selector -->
                <div class="form-group">
                    <label class="form-label">Transaction Type <span class="text-danger">*</span></label>
                    <div class="type-toggle-group">
                        <label class="type-toggle-option <?= ($defaultType === 'expense') ? 'active-expense' : '' ?>" id="labelTypeExpense">
                            <input type="radio" name="type" value="expense" id="typeExpense" 
                                   <?= ($defaultType === 'expense') ? 'checked' : '' ?>>
                            <span class="icon"><i class="fa-solid fa-arrow-up"></i></span>
                            <span class="text">Expense (Money Out)</span>
                        </label>

                        <label class="type-toggle-option <?= ($defaultType === 'income') ? 'active-income' : '' ?>" id="labelTypeIncome">
                            <input type="radio" name="type" value="income" id="typeIncome" 
                                   <?= ($defaultType === 'income') ? 'checked' : '' ?>>
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
                                       class="form-control" placeholder="0.00" required autofocus>
                            </div>
                            <div class="invalid-feedback" id="amountError"></div>
                            <span class="field-hint">Must be a positive decimal number greater than 0.</span>
                        </div>
                    </div>

                    <!-- 3. Transaction Date -->
                    <div class="form-col">
                        <div class="form-group">
                            <label for="transaction_date" class="form-label">Date of Transaction <span class="text-danger">*</span></label>
                            <div class="input-icon-wrap">
                                <i class="fa-regular fa-calendar input-icon"></i>
                                <input type="date" id="transaction_date" name="transaction_date" 
                                       class="form-control" value="<?= date('Y-m-d') ?>" required>
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
                                        <option value="<?= (int)$cat['id'] ?>" data-type="<?= e($cat['type']) ?>">
                                            <?= e($cat['name']) ?> (<?= ucfirst(e($cat['type'])) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="invalid-feedback" id="categoryError"></div>
                            <span class="field-hint">List dynamically updates based on the selected type above.</span>
                        </div>
                    </div>

                    <!-- 5. Description / Note -->
                    <div class="form-col">
                        <div class="form-group">
                            <label for="description" class="form-label">Description / Note</label>
                            <div class="input-icon-wrap">
                                <i class="fa-regular fa-comment-dots input-icon"></i>
                                <input type="text" id="description" name="description" class="form-control" 
                                       placeholder="e.g. Weekly Groceries, Client Milestone, Dinner" maxlength="255">
                            </div>
                            <div class="invalid-feedback" id="descriptionError"></div>
                        </div>
                    </div>
                </div>

                <!-- Form Action Buttons -->
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary btn-lg" id="submitTransactionBtn">
                        <i class="fa-solid fa-check"></i> Save Transaction
                    </button>
                    <a href="<?= e(app_url('pages/dashboard.php')) ?>" class="btn btn-secondary btn-lg">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

