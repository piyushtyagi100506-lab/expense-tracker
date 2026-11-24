<?php
/**
 * Analytical Dashboard View & Controller
 * 
 * Part of: Personal Expense Tracker Web Application
 * Web Application Development (WAD) Course Project
 * 
 * Explanatory Notes for Viva:
 * - Aggregates key financial performance metrics strictly filtered by session user_id.
 * - Computes monthly budget utilization percentage with conditional color thresholding (Green/Amber/Red).
 * - Prepares JSON payloads for Chart.js Category Doughnut and 6-Month Income vs. Expense comparison.
 * - Displays the 5 most recent transactions with category badges and formatted currency values.
 */

require_once __DIR__ . '/../includes/auth_check.php';

$pageTitle = 'Dashboard';
$activePage = 'dashboard';
$userId = get_user_id();

$currentMonth = (int)date('n');
$currentYear  = (int)date('Y');
$currentMonthName = date('F Y');

// 1. Fetch All-Time Financial Aggregates
$stmt = $pdo->prepare("
    SELECT 
        COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END), 0) AS total_income,
        COALESCE(SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END), 0) AS total_expense
    FROM transactions 
    WHERE user_id = :user_id
");
$stmt->execute([':user_id' => $userId]);
$totals = $stmt->fetch();

$totalIncome  = (float)$totals['total_income'];
$totalExpense = (float)$totals['total_expense'];
$netBalance   = $totalIncome - $totalExpense;

// 2. Fetch Current Month Financial Aggregates
$stmt = $pdo->prepare("
    SELECT 
        COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END), 0) AS month_income,
        COALESCE(SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END), 0) AS month_expense
    FROM transactions 
    WHERE user_id = :user_id 
      AND MONTH(transaction_date) = :m 
      AND YEAR(transaction_date) = :y
");
$stmt->execute([
    ':user_id' => $userId,
    ':m'       => $currentMonth,
    ':y'       => $currentYear
]);
$monthTotals = $stmt->fetch();

$monthIncome  = (float)$monthTotals['month_income'];
$monthExpense = (float)$monthTotals['month_expense'];
$monthSavings = $monthIncome - $monthExpense;

// 3. Fetch Monthly Budget Target for Current Month
$stmt = $pdo->prepare("
    SELECT amount 
    FROM budgets 
    WHERE user_id = :user_id AND month = :m AND year = :y 
    LIMIT 1
");
$stmt->execute([
    ':user_id' => $userId,
    ':m'       => $currentMonth,
    ':y'       => $currentYear
]);
$budgetRecord = $stmt->fetch();
$budgetAmount = $budgetRecord ? (float)$budgetRecord['amount'] : 0.00;

// Calculate budget progress
$budgetUsedPercent = 0;
$budgetRemaining   = 0;
$budgetStatusClass = 'progress-success'; // default green

if ($budgetAmount > 0) {
    $budgetRemaining = $budgetAmount - $monthExpense;
    $budgetUsedPercent = round(($monthExpense / $budgetAmount) * 100, 1);

    if ($budgetUsedPercent >= 100) {
        $budgetStatusClass = 'progress-danger'; // red
    } elseif ($budgetUsedPercent >= 80) {
        $budgetStatusClass = 'progress-warning'; // orange / amber
    }
}

// 4. Fetch 5 Most Recent Transactions
$stmt = $pdo->prepare("
    SELECT t.id, t.type, t.amount, t.description, t.transaction_date, c.name AS category_name
    FROM transactions t
    JOIN categories c ON t.category_id = c.id
    WHERE t.user_id = :user_id
    ORDER BY t.transaction_date DESC, t.id DESC
    LIMIT 5
");
$stmt->execute([':user_id' => $userId]);
$recentTransactions = $stmt->fetchAll();

// 5. Category Breakdown Data for Doughnut Chart (Current Month or All Time)
$stmt = $pdo->prepare("
    SELECT c.name, COALESCE(SUM(t.amount), 0) AS total
    FROM transactions t
    JOIN categories c ON t.category_id = c.id
    WHERE t.user_id = :user_id 
      AND t.type = 'expense'
    GROUP BY c.id, c.name
    HAVING total > 0
    ORDER BY total DESC
");
$stmt->execute([':user_id' => $userId]);
$categoryBreakdown = $stmt->fetchAll();

$catLabels = [];
$catValues = [];
foreach ($categoryBreakdown as $row) {
    $catLabels[] = $row['name'];
    $catValues[] = (float)$row['total'];
}

// 6. Last 6 Months Comparison Data for Bar Chart
// Generate calendar month keys for the last 6 months
$monthsData = [];
for ($i = 5; $i >= 0; $i--) {
    $time = strtotime("-$i months");
    $key  = date('Y-m', $time);
    $lbl  = date('M Y', $time);
    $monthsData[$key] = [
        'label'   => $lbl,
        'income'  => 0.0,
        'expense' => 0.0
    ];
}

$startSixMonths = date('Y-m-01', strtotime('-5 months'));
$stmt = $pdo->prepare("
    SELECT 
        DATE_FORMAT(transaction_date, '%Y-%m') AS ym,
        type,
        SUM(amount) AS total
    FROM transactions
    WHERE user_id = :user_id 
      AND transaction_date >= :start_date
    GROUP BY ym, type
    ORDER BY ym ASC
");
$stmt->execute([
    ':user_id'    => $userId,
    ':start_date' => $startSixMonths
]);
$rawTrend = $stmt->fetchAll();

foreach ($rawTrend as $r) {
    if (isset($monthsData[$r['ym']])) {
        if ($r['type'] === 'income') {
            $monthsData[$r['ym']]['income'] = (float)$r['total'];
        } else {
            $monthsData[$r['ym']]['expense'] = (float)$r['total'];
        }
    }
}

$trendLabels   = array_column($monthsData, 'label');
$trendIncomes  = array_column($monthsData, 'income');
$trendExpenses = array_column($monthsData, 'expense');

$pageScript = 'assets/js/charts.js';
include __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-page">
    <!-- Welcome Greeting Bar -->
    <div class="dashboard-welcome">
        <div>
            <h2>Welcome back, <?= e(get_user_name()) ?>!</h2>
            <p class="text-muted">Here is your financial summary for <strong><?= e($currentMonthName) ?></strong>.</p>
        </div>
        <div class="welcome-actions">
            <a href="<?= e(app_url('pages/add_transaction.php?type=income')) ?>" class="btn btn-outline-success btn-sm">
                <i class="fa-solid fa-arrow-down"></i> Add Income
            </a>
            <a href="<?= e(app_url('pages/add_transaction.php?type=expense')) ?>" class="btn btn-outline-danger btn-sm">
                <i class="fa-solid fa-arrow-up"></i> Add Expense
            </a>
            <a href="<?= e(app_url('pages/budget.php')) ?>" class="btn btn-secondary btn-sm">
                <i class="fa-solid fa-bullseye"></i> Set Budget
            </a>
        </div>
    </div>

    <!-- 1. Key Performance Indicator (KPI) Metric Cards -->
    <div class="metrics-grid">
        <!-- Total Income Card -->
        <div class="metric-card metric-income">
            <div class="metric-icon">
                <i class="fa-solid fa-arrow-trend-up"></i>
            </div>
            <div class="metric-info">
                <span class="metric-title">Total Income (All-Time)</span>
                <h3 class="metric-value text-success"><?= format_currency($totalIncome) ?></h3>
                <span class="metric-sub">This Month: <strong><?= format_currency($monthIncome) ?></strong></span>
            </div>
        </div>

        <!-- Total Expenses Card -->
        <div class="metric-card metric-expense">
            <div class="metric-icon">
                <i class="fa-solid fa-arrow-trend-down"></i>
            </div>
            <div class="metric-info">
                <span class="metric-title">Total Expenses (All-Time)</span>
                <h3 class="metric-value text-danger"><?= format_currency($totalExpense) ?></h3>
                <span class="metric-sub">This Month: <strong><?= format_currency($monthExpense) ?></strong></span>
            </div>
        </div>

        <!-- Net Balance Card -->
        <div class="metric-card metric-balance">
            <div class="metric-icon">
                <i class="fa-solid fa-scale-balanced"></i>
            </div>
            <div class="metric-info">
                <span class="metric-title">Net Available Balance</span>
                <h3 class="metric-value <?= ($netBalance >= 0) ? 'text-primary' : 'text-danger' ?>">
                    <?= format_currency($netBalance) ?>
                </h3>
                <span class="metric-sub">
                    Month Net: <strong class="<?= ($monthSavings >= 0) ? 'text-success' : 'text-danger' ?>">
                        <?= ($monthSavings >= 0 ? '+' : '') . format_currency($monthSavings) ?>
                    </strong>
                </span>
            </div>
        </div>
    </div>

    <!-- 2. Monthly Budget Progress Widget -->
    <div class="card budget-card">
        <div class="card-header flex-between">
            <div>
                <h3 class="card-title">
                    <i class="fa-solid fa-bullseye text-teal"></i>
                    Monthly Budget Overview &bull; <?= e($currentMonthName) ?>
                </h3>
            </div>
            <a href="<?= e(app_url('pages/budget.php')) ?>" class="btn btn-outline-primary btn-sm">
                <i class="fa-regular fa-pen-to-square"></i> Adjust Budget
            </a>
        </div>

        <div class="card-body">
            <?php if ($budgetAmount > 0): ?>
                <div class="budget-stat-row">
                    <div class="budget-stat-item">
                        <span class="label">Total Budget Limit</span>
                        <span class="val"><?= format_currency($budgetAmount) ?></span>
                    </div>
                    <div class="budget-stat-item">
                        <span class="label">Total Spent So Far</span>
                        <span class="val text-danger"><?= format_currency($monthExpense) ?></span>
                    </div>
                    <div class="budget-stat-item">
                        <span class="label">Remaining Spending Power</span>
                        <span class="val <?= ($budgetRemaining >= 0) ? 'text-success' : 'text-danger' ?>">
                            <?= format_currency($budgetRemaining) ?>
                        </span>
                    </div>
                    <div class="budget-stat-item">
                        <span class="label">Budget Consumed</span>
                        <span class="val"><?= e($budgetUsedPercent) ?>%</span>
                    </div>
                </div>

                <!-- Color-Coded Budget Progress Bar -->
                <div class="progress-bar-container">
                    <div class="progress-bar-track">
                        <div class="progress-bar-fill <?= e($budgetStatusClass) ?>" 
                             style="width: <?= min((float)$budgetUsedPercent, 100) ?>%;">
                        </div>
                    </div>
                </div>

                <!-- Budget Warning Notification when >= 80% or >= 100% -->
                <?php if ($budgetUsedPercent >= 100): ?>
                    <div class="budget-alert alert-danger-soft">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        <span>
                            <strong>Budget Limit Exceeded!</strong> You have spent <?= format_currency(abs($budgetRemaining)) ?> beyond your set monthly limit.
                        </span>
                    </div>
                <?php elseif ($budgetUsedPercent >= 80): ?>
                    <div class="budget-alert alert-warning-soft">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <span>
                            <strong>Approaching Limit!</strong> You have used <?= e($budgetUsedPercent) ?>% of your budget limit. Only <?= format_currency($budgetRemaining) ?> remaining.
                        </span>
                    </div>
                <?php else: ?>
                    <div class="budget-alert alert-success-soft">
                        <i class="fa-solid fa-circle-check"></i>
                        <span>
                            <strong>Healthy Spending!</strong> You are well within your allocated budget target for this month.
                        </span>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <div class="empty-budget-notice">
                    <p><i class="fa-solid fa-circle-info text-teal"></i> You have not configured a spending budget for <strong><?= e($currentMonthName) ?></strong> yet.</p>
                    <a href="<?= e(app_url('pages/budget.php')) ?>" class="btn btn-primary btn-sm">
                        <i class="fa-solid fa-plus"></i> Set <?= e($currentMonthName) ?> Budget
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- 3. Interactive Graphical Charts Section -->
    <div class="charts-grid">
        <!-- Chart 1: Expenses by Category (Doughnut) -->
        <div class="card chart-card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fa-solid fa-chart-pie text-teal"></i>
                    Expenses by Category
                </h3>
            </div>
            <div class="card-body">
                <?php if (!empty($catValues)): ?>
                    <div class="chart-wrapper">
                        <canvas id="categoryChart"></canvas>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fa-solid fa-receipt empty-icon"></i>
                        <p>No expense transactions recorded yet.</p>
                        <a href="<?= e(app_url('pages/add_transaction.php?type=expense')) ?>" class="btn btn-sm btn-outline-primary">Add an Expense</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Chart 2: 6-Month Income vs. Expense Trend (Bar Chart) -->
        <div class="card chart-card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fa-solid fa-chart-column text-teal"></i>
                    Income vs Expenses (Last 6 Months)
                </h3>
            </div>
            <div class="card-body">
                <div class="chart-wrapper">
                    <canvas id="trendChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- 4. Five Most Recent Transactions Table -->
    <div class="card recent-transactions-card">
        <div class="card-header flex-between">
            <h3 class="card-title">
                <i class="fa-solid fa-clock-rotate-left text-teal"></i>
                Recent Transactions
            </h3>
            <a href="<?= e(app_url('pages/history.php')) ?>" class="btn btn-outline-secondary btn-sm">
                View Full History <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>

        <div class="card-body p-0">
            <?php if (!empty($recentTransactions)): ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Category</th>
                                <th>Description</th>
                                <th class="text-right">Amount</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentTransactions as $tx): ?>
                                <tr>
                                    <td><?= e(format_date($tx['transaction_date'])) ?></td>
                                    <td>
                                        <span class="badge badge-<?= ($tx['type'] === 'income') ? 'success' : 'danger' ?>">
                                            <i class="fa-solid fa-<?= ($tx['type'] === 'income') ? 'arrow-down' : 'arrow-up' ?>"></i>
                                            <?= ucfirst(e($tx['type'])) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="category-tag"><?= e($tx['category_name']) ?></span>
                                    </td>
                                    <td>
                                        <?= !empty($tx['description']) ? e($tx['description']) : '<em class="text-muted">No description</em>' ?>
                                    </td>
                                    <td class="text-right font-semibold <?= ($tx['type'] === 'income') ? 'text-success' : 'text-danger' ?>">
                                        <?= ($tx['type'] === 'income' ? '+' : '-') . format_currency($tx['amount']) ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="action-btn-group">
                                            <a href="<?= e(app_url('pages/edit_transaction.php?id=' . $tx['id'])) ?>" 
                                               class="btn-icon" title="Edit Record">
                                                <i class="fa-regular fa-pen-to-square"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fa-solid fa-wallet empty-icon"></i>
                    <p>No transactions registered yet. Record your first transaction today!</p>
                    <a href="<?= e(app_url('pages/add_transaction.php')) ?>" class="btn btn-primary btn-sm">
                        <i class="fa-solid fa-plus"></i> Add Transaction
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Embedded JSON Data for Chart.js rendering -->
<script>
    window.chartData = {
        categories: {
            labels: <?= json_encode($catLabels) ?>,
            values: <?= json_encode($catValues) ?>
        },
        trends: {
            labels: <?= json_encode($trendLabels) ?>,
            incomes: <?= json_encode($trendIncomes) ?>,
            expenses: <?= json_encode($trendExpenses) ?>
        }
    };
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>

