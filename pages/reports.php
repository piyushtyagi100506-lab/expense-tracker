<?php
/**
 * Expense Reports & Financial Analytics View & Controller
 * 
 * Part of: Personal Expense Tracker Web Application
 * Web Application Development (WAD) Course Project
 * 
 * Explanatory Notes for Viva:
 * - Aggregates spending by category with percentage distribution and transaction counts.
 * - Month/Year dynamic filter to generate period-specific financial statements.
 * - Calculates Savings Rate (%) to reflect financial health.
 * - Supplies data to Chart.js for graphical distribution and 12-month annual comparison.
 * - Provides direct one-click CSV export for selected period.
 */

require_once __DIR__ . '/../includes/auth_check.php';

$pageTitle = 'Expense Reports';
$activePage = 'reports';
$userId = get_user_id();

$currentYear = (int)date('Y');
$currentMonth = (int)date('n');

// 1. Capture Filters (Defaults to Current Month & Year)
$selectedMonth = isset($_GET['month']) ? (int)$_GET['month'] : $currentMonth;
$selectedYear  = isset($_GET['year']) ? (int)$_GET['year'] : $currentYear;

$monthNames = [
    0  => 'Entire Year (All Months)',
    1  => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
    5  => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
    9  => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
];

// 2. Build SQL where conditions for selected timeframe
$whereSummary = "WHERE transactions.user_id = :user_id AND YEAR(transaction_date) = :year";
$whereReport = "WHERE t.user_id = :user_id AND YEAR(t.transaction_date) = :year";
$params = [
    ':user_id' => $userId,
    ':year'    => $selectedYear
];

if ($selectedMonth > 0) {
    $whereSummary .= " AND MONTH(transaction_date) = :month";
    $whereReport .= " AND MONTH(t.transaction_date) = :month";
    $params[':month'] = $selectedMonth;
}

// 3. Fetch Income & Expense totals for period
$stmt = $pdo->prepare("
    SELECT 
        COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END), 0) AS total_income,
        COALESCE(SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END), 0) AS total_expense,
        COUNT(id) AS total_transactions
    FROM transactions 
    $whereSummary
");
$stmt->execute($params);
$periodSummary = $stmt->fetch();

$periodIncome  = (float)$periodSummary['total_income'];
$periodExpense = (float)$periodSummary['total_expense'];
$periodSavings = $periodIncome - $periodExpense;
$periodTxCount = (int)$periodSummary['total_transactions'];

$savingsRate = ($periodIncome > 0) ? round(($periodSavings / $periodIncome) * 100, 1) : 0;

// 4. Spending by Category (Expenses only)
$stmt = $pdo->prepare("
    SELECT 
        c.name AS category_name,
        COALESCE(SUM(t.amount), 0) AS total_amount,
        COUNT(t.id) AS tx_count
    FROM transactions t
    JOIN categories c ON t.category_id = c.id
    $whereReport AND t.type = 'expense'
    GROUP BY c.id, c.name
    HAVING total_amount > 0
    ORDER BY total_amount DESC
");
$stmt->execute($params);
$categoryReport = $stmt->fetchAll();

$reportCatLabels = [];
$reportCatValues = [];
foreach ($categoryReport as $c) {
    $reportCatLabels[] = $c['category_name'];
    $reportCatValues[] = (float)$c['total_amount'];
}

// 5. 12-Month Annual Breakdown for Selected Year
$annualData = [];
for ($m = 1; $m <= 12; $m++) {
    $annualData[$m] = [
        'name'    => substr($monthNames[$m], 0, 3),
        'income'  => 0.0,
        'expense' => 0.0
    ];
}

$stmt = $pdo->prepare("
    SELECT 
        MONTH(transaction_date) AS m,
        type,
        SUM(amount) AS total
    FROM transactions
    WHERE user_id = :user_id AND YEAR(transaction_date) = :year
    GROUP BY m, type
    ORDER BY m ASC
");
$stmt->execute([
    ':user_id' => $userId,
    ':year'    => $selectedYear
]);
$annualRows = $stmt->fetchAll();

foreach ($annualRows as $row) {
    $m = (int)$row['m'];
    if (isset($annualData[$m])) {
        if ($row['type'] === 'income') {
            $annualData[$m]['income'] = (float)$row['total'];
        } else {
            $annualData[$m]['expense'] = (float)$row['total'];
        }
    }
}

$annualLabels   = array_column($annualData, 'name');
$annualIncomes  = array_column($annualData, 'income');
$annualExpenses = array_column($annualData, 'expense');

$pageScript = 'assets/js/charts.js';
include __DIR__ . '/../includes/header.php';
?>

<div class="reports-page">
    <!-- Period Filter & Export Toolbar -->
    <div class="card mb-4">
        <div class="card-body">
            <form action="<?= e(app_url('pages/reports.php')) ?>" method="GET" class="report-filter-bar">
                <div class="report-filter-controls">
                    <div class="filter-group">
                        <label for="month" class="form-label-sm">Report Month:</label>
                        <select name="month" id="month" class="form-control form-control-sm" onchange="this.form.submit()">
                            <?php foreach ($monthNames as $mNum => $mLabel): ?>
                                <option value="<?= $mNum ?>" <?= ($mNum === $selectedMonth) ? 'selected' : '' ?>>
                                    <?= $mLabel ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="year" class="form-label-sm">Report Year:</label>
                        <select name="year" id="year" class="form-control form-control-sm" onchange="this.form.submit()">
                            <?php for ($y = $currentYear - 3; $y <= $currentYear + 1; $y++): ?>
                                <option value="<?= $y ?>" <?= ($y === $selectedYear) ? 'selected' : '' ?>>
                                    <?= $y ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-secondary btn-sm">
                        <i class="fa-solid fa-arrows-rotate"></i> Refresh Report
                    </button>
                </div>

                <div class="report-export-button">
                    <a href="<?= e(app_url('actions/export_csv.php?month=' . $selectedMonth . '&year=' . $selectedYear)) ?>" 
                       class="btn btn-primary btn-sm">
                        <i class="fa-solid fa-file-arrow-down"></i> Export <?= e($selectedYear) ?> CSV Report
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Period KPI Summary Cards -->
    <div class="metrics-grid">
        <div class="metric-card metric-income">
            <div class="metric-icon"><i class="fa-solid fa-wallet"></i></div>
            <div class="metric-info">
                <span class="metric-title">Period Income</span>
                <h3 class="metric-value text-success"><?= format_currency($periodIncome) ?></h3>
                <span class="metric-sub"><?= $periodTxCount ?> Total Transactions</span>
            </div>
        </div>

        <div class="metric-card metric-expense">
            <div class="metric-icon"><i class="fa-solid fa-credit-card"></i></div>
            <div class="metric-info">
                <span class="metric-title">Period Expenses</span>
                <h3 class="metric-value text-danger"><?= format_currency($periodExpense) ?></h3>
                <span class="metric-sub">Money Disbursed</span>
            </div>
        </div>

        <div class="metric-card metric-balance">
            <div class="metric-icon"><i class="fa-solid fa-piggy-bank"></i></div>
            <div class="metric-info">
                <span class="metric-title">Net Savings</span>
                <h3 class="metric-value <?= ($periodSavings >= 0) ? 'text-teal' : 'text-danger' ?>">
                    <?= ($periodSavings >= 0 ? '+' : '') . format_currency($periodSavings) ?>
                </h3>
                <span class="metric-sub">
                    Savings Rate: <strong><?= e($savingsRate) ?>%</strong>
                </span>
            </div>
        </div>
    </div>

    <!-- Chart Grid -->
    <div class="charts-grid mt-4">
        <!-- 1. Category Expense Doughnut -->
        <div class="card chart-card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fa-solid fa-chart-pie text-teal"></i>
                    Expense Distribution by Category
                </h3>
            </div>
            <div class="card-body">
                <?php if (!empty($reportCatValues)): ?>
                    <div class="chart-wrapper">
                        <canvas id="categoryChart"></canvas>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fa-solid fa-receipt empty-icon"></i>
                        <p>No expenses found for the selected period.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- 2. Annual 12-Month Bar Chart -->
        <div class="card chart-card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fa-solid fa-chart-column text-teal"></i>
                    <?= $selectedYear ?> Monthly Cashflow Overview
                </h3>
            </div>
            <div class="card-body">
                <div class="chart-wrapper">
                    <canvas id="trendChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Category Breakdown Table -->
    <div class="card mt-4">
        <div class="card-header flex-between">
            <h3 class="card-title">
                <i class="fa-solid fa-table-list text-teal"></i>
                Detailed Category Spending Breakdown
            </h3>
            <span class="badge badge-info"><?= count($categoryReport) ?> Categories with Expenses</span>
        </div>
        <div class="card-body p-0">
            <?php if (!empty($categoryReport)): ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th class="text-center">Transactions</th>
                                <th class="text-right">Total Spent</th>
                                <th class="text-right">Share of Expense</th>
                                <th style="width: 28%;">Visualization</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categoryReport as $c): 
                                $catAmt = (float)$c['total_amount'];
                                $share = ($periodExpense > 0) ? round(($catAmt / $periodExpense) * 100, 1) : 0;
                            ?>
                                <tr>
                                    <td>
                                        <span class="category-tag font-medium"><?= e($c['category_name']) ?></span>
                                    </td>
                                    <td class="text-center text-muted"><?= (int)$c['tx_count'] ?></td>
                                    <td class="text-right font-semibold text-danger"><?= format_currency($catAmt) ?></td>
                                    <td class="text-right font-medium"><?= e($share) ?>%</td>
                                    <td>
                                        <div class="progress-bar-container" style="margin: 0;">
                                            <div class="progress-bar-track">
                                                <div class="progress-bar-fill progress-teal" style="width: <?= min((float)$share, 100) ?>%;"></div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="table-footer-total">
                                <th>Total Expenses</th>
                                <th class="text-center"><?= array_sum(array_column($categoryReport, 'tx_count')) ?></th>
                                <th class="text-right font-bold text-danger"><?= format_currency($periodExpense) ?></th>
                                <th class="text-right font-bold">100.0%</th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <p class="text-muted">No expense records found for <?= e($monthNames[$selectedMonth]) ?> <?= $selectedYear ?>.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Embedded JSON Data for Chart.js rendering -->
<script>
    window.chartData = {
        categories: {
            labels: <?= json_encode($reportCatLabels) ?>,
            values: <?= json_encode($reportCatValues) ?>
        },
        trends: {
            labels: <?= json_encode($annualLabels) ?>,
            incomes: <?= json_encode($annualIncomes) ?>,
            expenses: <?= json_encode($annualExpenses) ?>
        }
    };
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>

