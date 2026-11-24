<?php
/**
 * Recurring Bills & Subscriptions View & Controller
 * 
 * Part of: Personal Expense Tracker Web Application
 * Web Application Development (WAD) Course Project
 * 
 * Explanatory Notes for Viva:
 * - Tracks predictable periodic commitments (Netflix, gym, cloud services, rent, monthly payroll).
 * - "Post to Ledger" quick feature: Instantly registers the recurring charge into the primary transactions ledger.
 * - Calculates monthly projected recurring expense totals to aid budgeting foresight.
 */

require_once __DIR__ . '/../includes/auth_check.php';

$pageTitle = 'Recurring Bills & Subscriptions';
$activePage = 'recurring';
$userId = get_user_id();

// Fetch all recurring items with category name
$stmt = $pdo->prepare("
    SELECT r.*, c.name AS category_name
    FROM recurring_transactions r
    JOIN categories c ON r.category_id = c.id
    WHERE r.user_id = :user_id
    ORDER BY r.status ASC, r.next_due_date ASC
");
$stmt->execute([':user_id' => $userId]);
$recurringItems = $stmt->fetchAll();

// Calculate Monthly Projected Expense
$monthlyRecurringExpense = 0;
foreach ($recurringItems as $item) {
    if ($item['type'] === 'expense' && $item['status'] === 'active') {
        $amt = (float)$item['amount'];
        if ($item['frequency'] === 'monthly') {
            $monthlyRecurringExpense += $amt;
        } elseif ($item['frequency'] === 'weekly') {
            $monthlyRecurringExpense += ($amt * 4.33);
        } elseif ($item['frequency'] === 'yearly') {
            $monthlyRecurringExpense += ($amt / 12);
        }
    }
}

// Fetch categories for the modal
$categories = get_categories($pdo, null, $userId);

include __DIR__ . '/../includes/header.php';
?>

<div class="recurring-page">
    <div class="page-intro-header flex-between flex-wrap gap-3">
        <div>
            <h2>Recurring Bills &amp; Subscriptions</h2>
            <p class="text-muted">Manage recurring commitments like rent, streaming services, and utility bills.</p>
        </div>
        <button type="button" class="btn btn-primary" onclick="openCreateRecurringModal();">
            <i class="fa-solid fa-plus"></i> New Subscription
        </button>
    </div>

    <!-- Projected Expense Metric Banner -->
    <div class="metrics-grid mb-4">
        <div class="metric-card metric-expense">
            <div class="metric-icon"><i class="fa-solid fa-receipt"></i></div>
            <div class="metric-info">
                <span class="metric-title">Projected Monthly Fixed Commitments</span>
                <h3 class="metric-value text-danger"><?= format_currency($monthlyRecurringExpense) ?></h3>
                <span class="metric-sub">Across all active subscriptions and recurring bills</span>
            </div>
        </div>

        <div class="metric-card metric-balance">
            <div class="metric-icon"><i class="fa-solid fa-repeat"></i></div>
            <div class="metric-info">
                <span class="metric-title">Active Recurring Items</span>
                <h3 class="metric-value text-teal"><?= count(array_filter($recurringItems, fn($i) => $i['status'] === 'active')) ?></h3>
                <span class="metric-sub"><?= count($recurringItems) ?> total registered</span>
            </div>
        </div>
    </div>

    <!-- Recurring Items Table Card -->
    <div class="card">
        <div class="card-header flex-between">
            <h3 class="card-title">
                <i class="fa-solid fa-calendar-check text-teal"></i>
                Active Recurring Ledger
            </h3>
            <span class="text-xs text-muted">Click "Post to Ledger" when you pay the bill</span>
        </div>
        <div class="card-body p-0">
            <?php if (!empty($recurringItems)): ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Item Title</th>
                                <th>Category</th>
                                <th>Type</th>
                                <th>Frequency</th>
                                <th>Next Due Date</th>
                                <th class="text-right">Amount</th>
                                <th class="text-center">Quick Post</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recurringItems as $rec): 
                                $isDueSoon = (strtotime($rec['next_due_date']) <= strtotime('+7 days'));
                            ?>
                                <tr>
                                    <td>
                                        <strong><?= e($rec['title']) ?></strong>
                                    </td>
                                    <td>
                                        <span class="category-tag"><?= e($rec['category_name']) ?></span>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?= ($rec['type'] === 'income') ? 'success' : 'danger' ?>">
                                            <?= ucfirst(e($rec['type'])) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-secondary"><?= ucfirst(e($rec['frequency'])) ?></span>
                                    </td>
                                    <td>
                                        <span class="<?= $isDueSoon ? 'text-danger font-semibold' : '' ?>">
                                            <?= e(format_date($rec['next_due_date'])) ?>
                                            <?php if ($isDueSoon): ?>
                                                <i class="fa-solid fa-bell text-warning ml-1" title="Due within 7 days!"></i>
                                            <?php endif; ?>
                                        </span>
                                    </td>
                                    <td class="text-right font-semibold <?= ($rec['type'] === 'income') ? 'text-success' : 'text-danger' ?>">
                                        <?= ($rec['type'] === 'income' ? '+' : '-') . format_currency($rec['amount']) ?>
                                    </td>
                                    <td class="text-center">
                                        <!-- Post to Primary Transactions Table -->
                                        <form action="<?= e(app_url('actions/process_recurring.php')) ?>" method="POST" style="display:inline;">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= (int)$rec['id'] ?>">
                                            <button type="submit" class="btn btn-outline-primary btn-sm" title="Record this bill as a transaction right now">
                                                <i class="fa-solid fa-check"></i> Post to Ledger
                                            </button>
                                        </form>
                                    </td>
                                    <td class="text-center">
                                        <form action="<?= e(app_url('actions/delete_recurring.php')) ?>" method="POST" 
                                              onsubmit="return confirm('Delete this recurring subscription?');" style="display:inline;">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= (int)$rec['id'] ?>">
                                            <button type="submit" class="btn-icon text-danger" title="Delete">
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
                    <i class="fa-solid fa-repeat empty-icon"></i>
                    <h3>No Recurring Commitments Configured</h3>
                    <p class="text-muted">Track monthly bills like Netflix, rent, WiFi, and gym passes.</p>
                    <button type="button" class="btn btn-primary btn-sm mt-3" onclick="openCreateRecurringModal();">
                        <i class="fa-solid fa-plus"></i> Add Subscription
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal: Create Recurring Subscription -->
<div class="modal" id="createRecurringModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title"><i class="fa-solid fa-repeat text-teal"></i> New Recurring Commitment</h4>
                <button type="button" class="modal-close" onclick="closeCreateRecurringModal();">&times;</button>
            </div>
            <form action="<?= e(app_url('actions/save_recurring.php')) ?>" method="POST" novalidate>
                <?= csrf_field() ?>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="recTitle" class="form-label">Subscription / Bill Name <span class="text-danger">*</span></label>
                        <input type="text" name="title" id="recTitle" class="form-control" 
                               placeholder="e.g. Netflix, Spotify, Internet, Apartment Rent" required>
                    </div>

                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="recAmount" class="form-label">Amount (<?= get_currency_symbol() ?>) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0.01" name="amount" id="recAmount" 
                                       class="form-control" placeholder="0.00" required>
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label for="recType" class="form-label">Type <span class="text-danger">*</span></label>
                                <select name="type" id="recType" class="form-control" required>
                                    <option value="expense" selected>Expense (Money Out)</option>
                                    <option value="income">Income (Money In)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-col">
                            <div class="form-group">
                                <label for="recCategory" class="form-label">Category <span class="text-danger">*</span></label>
                                <select name="category_id" id="recCategory" class="form-control" required>
                                    <option value="">-- Select Category --</option>
                                    <?php foreach ($categories as $c): ?>
                                        <option value="<?= (int)$c['id'] ?>">
                                            <?= e($c['name']) ?> (<?= ucfirst(e($c['type'])) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-col">
                            <div class="form-group">
                                <label for="recFrequency" class="form-label">Frequency <span class="text-danger">*</span></label>
                                <select name="frequency" id="recFrequency" class="form-control" required>
                                    <option value="monthly" selected>Monthly</option>
                                    <option value="weekly">Weekly</option>
                                    <option value="yearly">Yearly</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="recNextDue" class="form-label">First / Next Due Date <span class="text-danger">*</span></label>
                        <input type="date" name="next_due_date" id="recNextDue" class="form-control" 
                               value="<?= date('Y-m-d') ?>" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeCreateRecurringModal();">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> Save Recurring Item</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function openCreateRecurringModal() {
        document.getElementById('createRecurringModal').classList.add('show');
    }
    function closeCreateRecurringModal() {
        document.getElementById('createRecurringModal').classList.remove('show');
    }
    window.addEventListener('click', (e) => {
        if (e.target.classList.contains('modal')) {
            e.target.classList.remove('show');
        }
    });
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
