<?php
/**
 * Monthly Budget Planner View & Controller
 * 
 * Part of: Personal Expense Tracker Web Application
 * Web Application Development (WAD) Course Project
 * 
 * Explanatory Notes for Viva:
 * - Allows users to establish monthly expense thresholds per calendar month and year.
 * - Compares budgeted allocations against actual expense totals computed using SQL SUM().
 * - Displays a dynamic progress bar that turns Orange at 80% and Red at 100% capacity.
 * - Displays historical budget variance (Saved vs. Overspent) for past months.
 */

require_once __DIR__ . '/../includes/auth_check.php';

$pageTitle = 'Monthly Budget';
$activePage = 'budget';
$userId = get_user_id();

$currentMonth = (int)date('n');
$currentYear  = (int)date('Y');

// 1. Fetch Current Active Month Budget
$stmt = $pdo->prepare("
    SELECT * FROM budgets 
    WHERE user_id = :user_id AND month = :m AND year = :y 
    LIMIT 1
");
$stmt->execute([
    ':user_id' => $userId,
    ':m'       => $currentMonth,
    ':y'       => $currentYear
]);
$currentBudget = $stmt->fetch();
$budgetAmount  = $currentBudget ? (float)$currentBudget['amount'] : 0.00;

// Fetch Actual Expenses for Current Month
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(amount), 0) AS total_expense
    FROM transactions
    WHERE user_id = :user_id 
      AND type = 'expense' 
      AND MONTH(transaction_date) = :m 
      AND YEAR(transaction_date) = :y
");
$stmt->execute([
    ':user_id' => $userId,
    ':m'       => $currentMonth,
    ':y'       => $currentYear
]);
$spentCurrent = (float)$stmt->fetch()['total_expense'];

$remainingCurrent = $budgetAmount - $spentCurrent;
$percentCurrent   = ($budgetAmount > 0) ? round(($spentCurrent / $budgetAmount) * 100, 1) : 0;

$statusClass = 'progress-success';
if ($percentCurrent >= 100) {
    $statusClass = 'progress-danger';
} elseif ($percentCurrent >= 80) {
    $statusClass = 'progress-warning';
}

// 2. Fetch All Budgets Configured by User (With Actual Expenses per Month)
$stmt = $pdo->prepare("
    SELECT 
        b.id, b.month, b.year, b.amount AS budget_amount,
        COALESCE(SUM(t.amount), 0) AS actual_spent
    FROM budgets b
    LEFT JOIN transactions t ON t.user_id = b.user_id 
                            AND t.type = 'expense' 
                            AND MONTH(t.transaction_date) = b.month 
                            AND YEAR(t.transaction_date) = b.year
    WHERE b.user_id = :user_id
    GROUP BY b.id, b.month, b.year, b.amount
    ORDER BY b.year DESC, b.month DESC
");
$stmt->execute([':user_id' => $userId]);
$budgetHistory = $stmt->fetchAll();

$monthNames = [
    1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
    5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
    9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
];

include __DIR__ . '/../includes/header.php';
?>

<div class="budget-page">
    <div class="page-intro-header">
        <h2>Monthly Budget Management</h2>
        <p class="text-muted">Set spending targets, avoid debt, and monitor your monthly discipline.</p>
    </div>

    <div class="grid-2-col">
        <!-- 1. Form to Set / Update Budget -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fa-solid fa-bullseye text-teal"></i>
                    Set Monthly Spending Limit
                </h3>
            </div>
            <div class="card-body">
                <form action="<?= e(app_url('actions/save_budget.php')) ?>" method="POST" id="budgetForm" novalidate>
                    <?= csrf_field() ?>

                    <div class="form-row">
                        <!-- Month Picker -->
                        <div class="form-col">
                            <div class="form-group">
                                <label for="month" class="form-label">Month <span class="text-danger">*</span></label>
                                <select name="month" id="month" class="form-control" required>
                                    <?php foreach ($monthNames as $mNum => $mLabel): ?>
                                        <option value="<?= $mNum ?>" <?= ($mNum === $currentMonth) ? 'selected' : '' ?>>
                                            <?= $mLabel ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Year Picker -->
                        <div class="form-col">
                            <div class="form-group">
                                <label for="year" class="form-label">Year <span class="text-danger">*</span></label>
                                <select name="year" id="year" class="form-control" required>
                                    <?php for ($y = $currentYear - 2; $y <= $currentYear + 2; $y++): ?>
                                        <option value="<?= $y ?>" <?= ($y === $currentYear) ? 'selected' : '' ?>>
                                            <?= $y ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Target Amount -->
                    <div class="form-group">
                        <label for="amount" class="form-label">Monthly Budget Limit (<?= CURRENCY_SYMBOL ?>) <span class="text-danger">*</span></label>
                        <div class="input-icon-wrap">
                            <span class="currency-prefix"><?= CURRENCY_SYMBOL ?></span>
                            <input type="number" step="0.01" min="1.00" name="amount" id="budgetAmountInput" 
                                   class="form-control" placeholder="e.g. 2500.00" 
                                   value="<?= ($budgetAmount > 0) ? e($budgetAmount) : '' ?>" required>
                        </div>
                        <div class="invalid-feedback" id="budgetAmountError"></div>
                        <span class="field-hint">Enter your maximum allowable spending limit for the chosen month.</span>
                    </div>

                    <div class="form-actions mt-3">
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fa-solid fa-floppy-disk"></i> Save Budget Target
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 2. Active Month Progress Visualization Card -->
        <div class="card">
            <div class="card-header flex-between">
                <h3 class="card-title">
                    <i class="fa-solid fa-chart-simple text-teal"></i>
                    Status for <?= e($monthNames[$currentMonth]) ?> <?= $currentYear ?>
                </h3>
                <span class="badge badge-<?= ($budgetAmount > 0) ? 'info' : 'secondary' ?>">
                    <?= ($budgetAmount > 0) ? 'Active' : 'Not Set' ?>
                </span>
            </div>
            <div class="card-body">
                <?php if ($budgetAmount > 0): ?>
                    <div class="budget-stat-grid">
                        <div class="budget-box">
                            <span class="b-label">Allocated Budget</span>
                            <span class="b-value text-teal font-semibold"><?= format_currency($budgetAmount) ?></span>
                        </div>
                        <div class="budget-box">
                            <span class="b-label">Spent to Date</span>
                            <span class="b-value text-danger font-semibold"><?= format_currency($spentCurrent) ?></span>
                        </div>
                        <div class="budget-box">
                            <span class="b-label">Remaining Power</span>
                            <span class="b-value font-semibold <?= ($remainingCurrent >= 0) ? 'text-success' : 'text-danger' ?>">
                                <?= format_currency($remainingCurrent) ?>
                            </span>
                        </div>
                        <div class="budget-box">
                            <span class="b-label">Consumption</span>
                            <span class="b-value font-semibold"><?= e($percentCurrent) ?>%</span>
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div class="progress-bar-container mt-4">
                        <div class="progress-bar-track">
                            <div class="progress-bar-fill <?= e($statusClass) ?>" 
                                 style="width: <?= min((float)$percentCurrent, 100) ?>%;">
                            </div>
                        </div>
                    </div>

                    <!-- Dynamic Alerts -->
                    <div class="mt-3">
                        <?php if ($percentCurrent >= 100): ?>
                            <div class="budget-alert alert-danger-soft">
                                <i class="fa-solid fa-triangle-exclamation"></i>
                                <span>
                                    <strong>Limit Breached!</strong> You have exceeded your budget by <?= format_currency(abs($remainingCurrent)) ?>! Avoid non-essential spending.
                                </span>
                            </div>
                        <?php elseif ($percentCurrent >= 80): ?>
                            <div class="budget-alert alert-warning-soft">
                                <i class="fa-solid fa-circle-exclamation"></i>
                                <span>
                                    <strong>Caution!</strong> You have spent <?= e($percentCurrent) ?>% of your budget. Only <?= format_currency($remainingCurrent) ?> left.
                                </span>
                            </div>
                        <?php else: ?>
                            <div class="budget-alert alert-success-soft">
                                <i class="fa-solid fa-circle-check"></i>
                                <span>
                                    <strong>On Track!</strong> Spending is well within limits. Keep it up!
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>

                <?php else: ?>
                    <div class="empty-state">
                        <i class="fa-solid fa-calculator empty-icon"></i>
                        <p>No budget set for <?= e($monthNames[$currentMonth]) ?> <?= $currentYear ?>.</p>
                        <p class="text-muted text-sm">Use the form on the left to set your spending limit.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- 3. Interactive What-If Budget Scenario Simulator (Client-Side JS Engine) -->
    <div class="simulator-card mt-4">
        <div class="simulator-header">
            <div>
                <h3 class="simulator-title">
                    <i class="fa-solid fa-sliders text-teal"></i>
                    What-If Spending Scenario Simulator
                </h3>
                <p class="text-muted text-sm">Adjust the slider to simulate additional purchases or savings and preview your updated financial standing in real time.</p>
            </div>
            <span class="simulator-badge">Client-Side JavaScript Engine</span>
        </div>

        <div class="simulator-slider-group">
            <div class="slider-label-row">
                <span class="slider-name">Simulate Additional Monthly Spending / Savings:</span>
                <span class="slider-current-val" id="budgetSimVal">+<?= CURRENCY_SYMBOL ?>0.00</span>
            </div>
            <input type="range" class="custom-range-slider" id="budgetSimSlider" 
                   min="-500" max="1500" step="25" value="0"
                   data-budget="<?= (float)$budgetAmount ?>" 
                   data-spent="<?= (float)$spentCurrent ?>">
            <div class="flex-between text-xs text-muted mt-2">
                <span>-<?= CURRENCY_SYMBOL ?>500 (Cut Expenses)</span>
                <span>Baseline (Current: <?= format_currency($spentCurrent) ?>)</span>
                <span>+<?= CURRENCY_SYMBOL ?>1500 (Big Purchases)</span>
            </div>
        </div>

        <div class="simulator-grid">
            <div class="sim-metric-box">
                <span class="sim-metric-title">Simulated Remaining</span>
                <span class="sim-metric-val text-success" id="simRemainingVal"><?= format_currency($remainingCurrent) ?></span>
                <span class="sim-metric-sub">After Simulated Spend</span>
            </div>
            <div class="sim-metric-box">
                <span class="sim-metric-title">Projected Usage</span>
                <span class="sim-metric-val" id="simPercentVal"><?= e($percentCurrent) ?>%</span>
                <span class="sim-metric-sub">Of <?= format_currency($budgetAmount) ?> Limit</span>
            </div>
            <div class="sim-metric-box">
                <span class="sim-metric-title">Daily Spending Limit</span>
                <span class="sim-metric-val text-teal" id="simDailyVal">Calculating...</span>
                <span class="sim-metric-sub">Recommended Per Day</span>
            </div>
        </div>

        <div class="progress-bar-container mt-4">
            <div class="progress-bar-track">
                <div class="progress-bar-fill <?= e($statusClass) ?>" id="simProgressBar" style="width: <?= min((float)$percentCurrent, 100) ?>%;"></div>
            </div>
        </div>

        <div class="sim-status-banner sim-status-safe" id="simStatusBanner">
            <i class="fa-solid fa-circle-check"></i>
            <span><strong>Optimal Financial Health!</strong> You have plenty of budget headroom for discretionary spending.</span>
        </div>
    </div>

    <!-- 3. Historical Budgets Table -->
    <div class="card mt-4">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fa-solid fa-timeline text-teal"></i>
                Budget Performance History
            </h3>
        </div>
        <div class="card-body p-0">
            <?php if (!empty($budgetHistory)): ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Period</th>
                                <th class="text-right">Budget Limit</th>
                                <th class="text-right">Actual Spent</th>
                                <th class="text-right">Variance / Savings</th>
                                <th class="text-center">Utilization</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($budgetHistory as $b): 
                                $bLimit = (float)$b['budget_amount'];
                                $bSpent = (float)$b['actual_spent'];
                                $bVar   = $bLimit - $bSpent;
                                $bPct   = ($bLimit > 0) ? round(($bSpent / $bLimit) * 100, 1) : 0;
                            ?>
                                <tr>
                                    <td>
                                        <strong><?= e($monthNames[(int)$b['month']] ?? 'Month ' . $b['month']) ?> <?= (int)$b['year'] ?></strong>
                                    </td>
                                    <td class="text-right font-medium"><?= format_currency($bLimit) ?></td>
                                    <td class="text-right font-medium text-danger"><?= format_currency($bSpent) ?></td>
                                    <td class="text-right font-semibold <?= ($bVar >= 0) ? 'text-success' : 'text-danger' ?>">
                                        <?= ($bVar >= 0 ? '+' : '') . format_currency($bVar) ?>
                                    </td>
                                    <td class="text-center"><?= e($bPct) ?>%</td>
                                    <td class="text-center">
                                        <?php if ($bPct > 100): ?>
                                            <span class="badge badge-danger">Over Budget</span>
                                        <?php elseif ($bPct >= 80): ?>
                                            <span class="badge badge-warning">Near Limit</span>
                                        <?php else: ?>
                                            <span class="badge badge-success">Under Budget</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <p class="text-muted">No budget logs found. Configure your first budget above.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

