<?php
/**
 * Category Management View & Controller
 * 
 * Part of: Personal Expense Tracker Web Application
 * Web Application Development (WAD) Course Project
 * 
 * Explanatory Notes for Viva:
 * - Allows users to manage custom income & expense categories alongside system defaults.
 * - Enforces database constraints: user-created categories are scoped strictly by user_id.
 * - Prevents accidental orphaned records by validating transactions before deleting a category.
 */

require_once __DIR__ . '/../includes/auth_check.php';

$pageTitle = 'Category Management';
$activePage = 'categories';
$userId = get_user_id();

// Fetch System Defaults (user_id IS NULL)
$stmt = $pdo->prepare("
    SELECT id, name, type, 'default' AS origin 
    FROM categories 
    WHERE user_id IS NULL 
    ORDER BY type ASC, name ASC
");
$stmt->execute();
$defaultCategories = $stmt->fetchAll();

// Fetch User's Custom Categories (user_id = :user_id)
$stmt = $pdo->prepare("
    SELECT c.id, c.name, c.type, 'custom' AS origin,
           COUNT(t.id) AS usage_count
    FROM categories c
    LEFT JOIN transactions t ON t.category_id = c.id AND t.user_id = :transaction_user_id
    WHERE c.user_id = :category_user_id
    GROUP BY c.id, c.name, c.type
    ORDER BY c.type ASC, c.name ASC
");
$stmt->execute([
    ':transaction_user_id' => $userId,
    ':category_user_id'    => $userId
]);
$customCategories = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="categories-page">
    <div class="page-intro-header">
        <h2>Category Management</h2>
        <p class="text-muted">Personalize your accounts by creating custom income and expense categories.</p>
    </div>

    <div class="grid-2-col">
        <!-- 1. Add New Custom Category Form -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fa-solid fa-plus-circle text-teal"></i>
                    Add Custom Category
                </h3>
            </div>
            <div class="card-body">
                <form action="<?= e(app_url('actions/save_category.php')) ?>" method="POST" id="categoryForm" novalidate>
                    <?= csrf_field() ?>

                    <div class="form-group">
                        <label for="name" class="form-label">Category Name <span class="text-danger">*</span></label>
                        <div class="input-icon-wrap">
                            <i class="fa-solid fa-tag input-icon"></i>
                            <input type="text" name="name" id="categoryNameInput" class="form-control" 
                                   placeholder="e.g. Pet Care, Cryptos, Gym, Subscriptions" maxlength="60" required autofocus>
                        </div>
                        <div class="invalid-feedback" id="catNameError"></div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Category Type <span class="text-danger">*</span></label>
                        <div class="type-toggle-group">
                            <label class="type-toggle-option active-expense" id="catLabelExpense">
                                <input type="radio" name="type" value="expense" id="catTypeExpense" checked>
                                <span class="icon"><i class="fa-solid fa-arrow-up"></i></span>
                                <span class="text">Expense</span>
                            </label>
                            <label class="type-toggle-option" id="catLabelIncome">
                                <input type="radio" name="type" value="income" id="catTypeIncome">
                                <span class="icon"><i class="fa-solid fa-arrow-down"></i></span>
                                <span class="text">Income</span>
                            </label>
                        </div>
                    </div>

                    <div class="form-actions mt-3">
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fa-solid fa-check"></i> Create Category
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 2. Custom Categories List -->
        <div class="card">
            <div class="card-header flex-between">
                <h3 class="card-title">
                    <i class="fa-solid fa-user-tag text-teal"></i>
                    Your Custom Categories
                </h3>
                <span class="badge badge-info"><?= count($customCategories) ?> Active</span>
            </div>
            <div class="card-body p-0">
                <?php if (!empty($customCategories)): ?>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    <th>Type</th>
                                    <th class="text-center">Linked Records</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($customCategories as $cat): ?>
                                    <tr>
                                        <td>
                                            <span class="category-tag font-semibold"><?= e($cat['name']) ?></span>
                                        </td>
                                        <td>
                                            <span class="badge badge-<?= ($cat['type'] === 'income') ? 'success' : 'danger' ?>">
                                                <?= ucfirst(e($cat['type'])) ?>
                                            </span>
                                        </td>
                                        <td class="text-center text-muted">
                                            <?= (int)$cat['usage_count'] ?> transaction(s)
                                        </td>
                                        <td class="text-center">
                                            <form action="<?= e(app_url('actions/delete_category.php')) ?>" method="POST" 
                                                  onsubmit="return confirm('Are you sure you want to delete this custom category?');"
                                                  style="display:inline;">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="id" value="<?= (int)$cat['id'] ?>">
                                                <button type="submit" class="btn-icon text-danger" title="Delete Category">
                                                    <i class="fa-regular fa-trash-can"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fa-solid fa-tags empty-icon"></i>
                        <p>You haven't created any custom categories yet.</p>
                        <p class="text-muted text-sm">Add one using the form on the left to categorize custom expenses!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- 3. Default System Categories (Read-Only) -->
    <div class="card mt-4">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fa-solid fa-lock text-muted"></i>
                Pre-Configured System Categories
            </h3>
            <p class="card-subtitle">These baseline categories are available automatically to all user accounts.</p>
        </div>
        <div class="card-body">
            <div class="category-pills-wrap">
                <?php foreach ($defaultCategories as $dCat): ?>
                    <div class="category-pill-item">
                        <span class="badge badge-<?= ($dCat['type'] === 'income') ? 'success' : 'danger' ?> badge-dot">
                            <i class="fa-solid fa-<?= ($dCat['type'] === 'income') ? 'arrow-down' : 'arrow-up' ?>"></i>
                        </span>
                        <span class="cat-pill-name"><?= e($dCat['name']) ?></span>
                        <span class="cat-pill-type text-muted">(<?= ucfirst(e($dCat['type'])) ?>)</span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script>
    // Radio toggle styling
    const catLabelExpense = document.getElementById('catLabelExpense');
    const catLabelIncome = document.getElementById('catLabelIncome');
    const catTypeExpense = document.getElementById('catTypeExpense');
    const catTypeIncome = document.getElementById('catTypeIncome');

    catTypeExpense?.addEventListener('change', () => {
        catLabelExpense.classList.add('active-expense');
        catLabelIncome.classList.remove('active-income');
    });

    catTypeIncome?.addEventListener('change', () => {
        catLabelIncome.classList.add('active-income');
        catLabelExpense.classList.remove('active-expense');
    });
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
