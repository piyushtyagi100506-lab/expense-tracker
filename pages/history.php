<?php
/**
 * Transaction History, Search, Filter & Pagination
 * 
 * Part of: Personal Expense Tracker Web Application
 * Web Application Development (WAD) Course Project
 * 
 * Explanatory Notes for Viva:
 * - Dynamic SQL Query Building: Combines multiple filter criteria while maintaining prepared statement parameter binding.
 * - Server-side pagination (10 records/page) using SQL LIMIT & OFFSET with integer type casting.
 * - Real-time filtered aggregate metrics (shows total income, total expense, and balance of the active filtered query).
 * - CSRF-protected deletion forms with JavaScript confirmation safeguards.
 * - View transaction modal for inspecting full record details.
 */

require_once __DIR__ . '/../includes/auth_check.php';

$pageTitle = 'Transaction History';
$activePage = 'history';
$userId = get_user_id();

// 1. Capture & Sanitize Filter Parameters
$searchKeyword = trim($_GET['q'] ?? '');
$filterType    = trim($_GET['type'] ?? 'all');
$filterCategory= (int)($_GET['category'] ?? 0);
$fromDate      = trim($_GET['from_date'] ?? '');
$toDate        = trim($_GET['to_date'] ?? '');
$sortOption    = trim($_GET['sort'] ?? 'date_desc');
$currentPage   = max(1, (int)($_GET['page'] ?? 1));
$perPage       = 10;

// 2. Build Dynamic SQL Filter Clauses
$whereClauses = ["t.user_id = :user_id"];
$params = [':user_id' => $userId];

// Search keyword in description or category name
if ($searchKeyword !== '') {
    $whereClauses[] = "(t.description LIKE :search OR c.name LIKE :search)";
    $params[':search'] = '%' . $searchKeyword . '%';
}

// Transaction type filter
if (in_array($filterType, ['income', 'expense'], true)) {
    $whereClauses[] = "t.type = :type";
    $params[':type'] = $filterType;
}

// Category filter
if ($filterCategory > 0) {
    $whereClauses[] = "t.category_id = :category_id";
    $params[':category_id'] = $filterCategory;
}

// Date range filters
if (!empty($fromDate)) {
    $whereClauses[] = "t.transaction_date >= :from_date";
    $params[':from_date'] = $fromDate;
}
if (!empty($toDate)) {
    $whereClauses[] = "t.transaction_date <= :to_date";
    $params[':to_date'] = $toDate;
}

$whereSql = "WHERE " . implode(" AND ", $whereClauses);

// 3. Sorting SQL Map
$orderSql = "ORDER BY t.transaction_date DESC, t.id DESC";
switch ($sortOption) {
    case 'date_asc':
        $orderSql = "ORDER BY t.transaction_date ASC, t.id ASC";
        break;
    case 'amount_desc':
        $orderSql = "ORDER BY t.amount DESC, t.transaction_date DESC";
        break;
    case 'amount_asc':
        $orderSql = "ORDER BY t.amount ASC, t.transaction_date DESC";
        break;
    default:
        $orderSql = "ORDER BY t.transaction_date DESC, t.id DESC";
        break;
}

// 4. Calculate Total Filtered Count & Financial Aggregates
$countSql = "
    SELECT 
        COUNT(*) AS total_count,
        COALESCE(SUM(CASE WHEN t.type = 'income' THEN t.amount ELSE 0 END), 0) AS filtered_income,
        COALESCE(SUM(CASE WHEN t.type = 'expense' THEN t.amount ELSE 0 END), 0) AS filtered_expense
    FROM transactions t
    JOIN categories c ON t.category_id = c.id
    $whereSql
";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$aggregateData = $countStmt->fetch();

$totalRecords   = (int)$aggregateData['total_count'];
$filteredIncome = (float)$aggregateData['filtered_income'];
$filteredExpense= (float)$aggregateData['filtered_expense'];
$filteredNet    = $filteredIncome - $filteredExpense;
$totalPages     = max(1, (int)ceil($totalRecords / $perPage));

// Prevent page overflow
if ($currentPage > $totalPages && $totalRecords > 0) {
    $currentPage = $totalPages;
}
$offset = ($currentPage - 1) * $perPage;

// 5. Fetch Paginated Records
$recordsSql = "
    SELECT 
        t.id, t.user_id, t.category_id, t.type, t.amount, t.description, 
        t.transaction_date, t.created_at, c.name AS category_name
    FROM transactions t
    JOIN categories c ON t.category_id = c.id
    $whereSql
    $orderSql
    LIMIT :limit OFFSET :offset
";
$recordsStmt = $pdo->prepare($recordsSql);

// Bind base params
foreach ($params as $key => $val) {
    $recordsStmt->bindValue($key, $val);
}
// Bind integer limit and offset
$recordsStmt->bindValue(':limit', (int)$perPage, PDO::PARAM_INT);
$recordsStmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
$recordsStmt->execute();
$transactions = $recordsStmt->fetchAll();

// Fetch all categories for filter dropdown
$categories = get_categories($pdo, null, $userId);

// Helper function to retain query parameters during pagination
function build_page_url($page) {
    $query = $_GET;
    $query['page'] = $page;
    return '?' . http_build_query($query);
}

include __DIR__ . '/../includes/header.php';
?>

<div class="history-page">
    <!-- Filter Card Toolbar -->
    <div class="card filter-card">
        <div class="card-header flex-between">
            <h3 class="card-title">
                <i class="fa-solid fa-filter text-teal"></i>
                Search &amp; Filter Transactions
            </h3>
            <div class="filter-actions">
                <button type="button" class="btn btn-outline-primary btn-sm" onclick="exportTransactionsToCSV()" title="Instant Client-Side CSV Export (Zero Roundtrip)">
                    <i class="fa-solid fa-file-csv"></i> Instant CSV
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="exportTransactionsToJSON()" title="Instant Client-Side JSON Export">
                    <i class="fa-solid fa-file-code"></i> JSON
                </button>
                <a href="<?= e(app_url('actions/export_csv.php?' . http_build_query($_GET))) ?>" class="btn btn-outline-secondary btn-sm" title="Server-side PHP Export">
                    <i class="fa-solid fa-download"></i> Server CSV
                </a>
                <a href="<?= e(app_url('pages/history.php')) ?>" class="btn btn-outline-secondary btn-sm" title="Clear all filters">
                    <i class="fa-solid fa-rotate-left"></i> Reset
                </a>
            </div>
        </div>

        <div class="card-body">
            <form action="<?= e(app_url('pages/history.php')) ?>" method="GET" class="filter-form" id="historyFilterForm">
                <div class="filter-grid">
                    <!-- Search Text -->
                    <div class="filter-field">
                        <label for="q" class="form-label-sm">Keyword Search</label>
                        <div class="input-icon-wrap">
                            <i class="fa-solid fa-magnifying-glass input-icon"></i>
                            <input type="text" name="q" id="q" class="form-control form-control-sm" 
                                   placeholder="Description or category..." value="<?= e($searchKeyword) ?>">
                        </div>
                    </div>

                    <!-- Type Filter -->
                    <div class="filter-field">
                        <label for="type" class="form-label-sm">Transaction Type</label>
                        <select name="type" id="type" class="form-control form-control-sm">
                            <option value="all" <?= ($filterType === 'all') ? 'selected' : '' ?>>All Types</option>
                            <option value="income" <?= ($filterType === 'income') ? 'selected' : '' ?>>Income Only</option>
                            <option value="expense" <?= ($filterType === 'expense') ? 'selected' : '' ?>>Expense Only</option>
                        </select>
                    </div>

                    <!-- Category Filter -->
                    <div class="filter-field">
                        <label for="category" class="form-label-sm">Category</label>
                        <select name="category" id="category" class="form-control form-control-sm">
                            <option value="0">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= (int)$cat['id'] ?>" <?= ($filterCategory === (int)$cat['id']) ? 'selected' : '' ?>>
                                    <?= e($cat['name']) ?> (<?= ucfirst(e($cat['type'])) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- From Date -->
                    <div class="filter-field">
                        <label for="from_date" class="form-label-sm">From Date</label>
                        <input type="date" name="from_date" id="from_date" class="form-control form-control-sm" 
                               value="<?= e($fromDate) ?>">
                    </div>

                    <!-- To Date -->
                    <div class="filter-field">
                        <label for="to_date" class="form-label-sm">To Date</label>
                        <input type="date" name="to_date" id="to_date" class="form-control form-control-sm" 
                               value="<?= e($toDate) ?>">
                    </div>

                    <!-- Sort Order -->
                    <div class="filter-field">
                        <label for="sort" class="form-label-sm">Sort By</label>
                        <select name="sort" id="sort" class="form-control form-control-sm">
                            <option value="date_desc" <?= ($sortOption === 'date_desc') ? 'selected' : '' ?>>Date (Newest First)</option>
                            <option value="date_asc" <?= ($sortOption === 'date_asc') ? 'selected' : '' ?>>Date (Oldest First)</option>
                            <option value="amount_desc" <?= ($sortOption === 'amount_desc') ? 'selected' : '' ?>>Amount (Highest First)</option>
                            <option value="amount_asc" <?= ($sortOption === 'amount_asc') ? 'selected' : '' ?>>Amount (Lowest First)</option>
                        </select>
                    </div>
                </div>

                <div class="filter-submit-row">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fa-solid fa-filter"></i> Apply Filters
                    </button>
                    <span class="text-muted text-sm ml-2">
                        Showing <?= count($transactions) ?> of <?= $totalRecords ?> total matching records
                    </span>
                </div>
            </form>
        </div>
    </div>

    <!-- Active Query Aggregate Mini Bar -->
    <div class="filter-summary-bar">
        <div class="summary-pill">
            <span class="label">Filtered Income:</span>
            <span class="value text-success font-semibold" id="filteredIncomeVal"><?= format_currency($filteredIncome) ?></span>
        </div>
        <div class="summary-pill">
            <span class="label">Filtered Expenses:</span>
            <span class="value text-danger font-semibold" id="filteredExpenseVal"><?= format_currency($filteredExpense) ?></span>
        </div>
        <div class="summary-pill">
            <span class="label">Filtered Net:</span>
            <span class="value font-semibold <?= ($filteredNet >= 0) ? 'text-primary' : 'text-danger' ?>" id="filteredBalanceVal">
                <?= ($filteredNet >= 0 ? '+' : '') . format_currency($filteredNet) ?>
            </span>
        </div>
        <div class="summary-pill" style="margin-left: auto;">
            <span class="live-counter-badge" id="filteredCountBadge">Showing <?= count($transactions) ?> of <?= $totalRecords ?> items</span>
        </div>
    </div>

    <!-- Transaction Table Card -->
    <div class="card table-card">
        <div class="card-body p-0">
            <?php if (!empty($transactions)): ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Category</th>
                                <th>Description</th>
                                <th class="text-right">Amount</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $rowNum = $offset + 1;
                            foreach ($transactions as $tx): 
                            ?>
                                <tr>
                                    <td class="text-muted"><?= $rowNum++ ?></td>
                                    <td>
                                        <strong><?= e(format_date($tx['transaction_date'])) ?></strong>
                                    </td>
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
                                            <!-- View Details Modal Trigger Button -->
                                            <button type="button" class="btn-icon text-info view-tx-btn" 
                                                    title="View Details"
                                                    data-id="<?= (int)$tx['id'] ?>"
                                                    data-date="<?= e(format_date($tx['transaction_date'])) ?>"
                                                    data-type="<?= ucfirst(e($tx['type'])) ?>"
                                                    data-category="<?= e($tx['category_name']) ?>"
                                                    data-amount="<?= format_currency($tx['amount']) ?>"
                                                    data-desc="<?= e($tx['description'] ?? 'None') ?>"
                                                    data-created="<?= e(format_date($tx['created_at'], 'M d, Y h:i A')) ?>">
                                                <i class="fa-regular fa-eye"></i>
                                            </button>

                                            <!-- Receipt Voucher Trigger Button (JavaScript Modal) -->
                                            <button type="button" class="btn-icon text-success" 
                                                    title="Generate Receipt Voucher"
                                                    onclick="showTransactionReceipt('<?= e(format_date($tx['transaction_date'])) ?>', '<?= e($tx['type']) ?>', '<?= e($tx['category_name']) ?>', '<?= e(addslashes($tx['description'] ?? '')) ?>', <?= (float)$tx['amount'] ?>)">
                                                <i class="fa-solid fa-receipt"></i>
                                            </button>

                                            <!-- Edit Record -->
                                            <a href="<?= e(app_url('pages/edit_transaction.php?id=' . $tx['id'])) ?>" 
                                               class="btn-icon text-primary" title="Edit Record">
                                                <i class="fa-regular fa-pen-to-square"></i>
                                            </a>

                                            <!-- Delete Record Form (CSRF Protected) -->
                                            <form action="<?= e(app_url('actions/delete_transaction.php')) ?>" 
                                                  method="POST" class="delete-form" style="display:inline;"
                                                  onsubmit="return confirm('Are you sure you want to permanently delete this transaction?');">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="id" value="<?= (int)$tx['id'] ?>">
                                                <button type="submit" class="btn-icon text-danger" title="Delete Record">
                                                    <i class="fa-regular fa-trash-can"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Component -->
                <?php if ($totalPages > 1): ?>
                    <div class="pagination-wrapper">
                        <div class="pagination-info">
                            Page <?= $currentPage ?> of <?= $totalPages ?> (Total: <?= $totalRecords ?> items)
                        </div>
                        <ul class="pagination-list">
                            <!-- Previous Page Link -->
                            <?php if ($currentPage > 1): ?>
                                <li class="page-item">
                                    <a href="<?= e(build_page_url($currentPage - 1)) ?>" class="page-link" aria-label="Previous">
                                        <i class="fa-solid fa-chevron-left"></i>
                                    </a>
                                </li>
                            <?php else: ?>
                                <li class="page-item disabled">
                                    <span class="page-link"><i class="fa-solid fa-chevron-left"></i></span>
                                </li>
                            <?php endif; ?>

                            <!-- Page Numbers (Window of 5) -->
                            <?php 
                            $startPage = max(1, $currentPage - 2);
                            $endPage   = min($totalPages, $startPage + 4);
                            if ($endPage - $startPage < 4) {
                                $startPage = max(1, $endPage - 4);
                            }

                            for ($p = $startPage; $p <= $endPage; $p++): 
                            ?>
                                <li class="page-item <?= ($p === $currentPage) ? 'active' : '' ?>">
                                    <a href="<?= e(build_page_url($p)) ?>" class="page-link"><?= $p ?></a>
                                </li>
                            <?php endfor; ?>

                            <!-- Next Page Link -->
                            <?php if ($currentPage < $totalPages): ?>
                                <li class="page-item">
                                    <a href="<?= e(build_page_url($currentPage + 1)) ?>" class="page-link" aria-label="Next">
                                        <i class="fa-solid fa-chevron-right"></i>
                                    </a>
                                </li>
                            <?php else: ?>
                                <li class="page-item disabled">
                                    <span class="page-link"><i class="fa-solid fa-chevron-right"></i></span>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <div class="empty-state">
                    <i class="fa-solid fa-search empty-icon"></i>
                    <h3>No Transactions Found</h3>
                    <p>No records matched your search or filter criteria. Try adjusting your filters.</p>
                    <a href="<?= e(app_url('pages/history.php')) ?>" class="btn btn-outline-primary btn-sm">Reset Filters</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Transaction Inspection Modal -->
<div class="modal" id="txModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="modalTxTitle">Transaction Details</h4>
                <button type="button" class="modal-close" onclick="closeTxModal();">&times;</button>
            </div>
            <div class="modal-body">
                <div class="detail-list">
                    <div class="detail-row">
                        <span class="detail-label">Transaction ID:</span>
                        <span class="detail-value" id="modalTxId">#</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Type:</span>
                        <span class="detail-value" id="modalTxType">-</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Amount:</span>
                        <span class="detail-value font-semibold text-lg" id="modalTxAmount">-</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Category:</span>
                        <span class="detail-value" id="modalTxCategory">-</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Transaction Date:</span>
                        <span class="detail-value" id="modalTxDate">-</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Description / Note:</span>
                        <span class="detail-value" id="modalTxDesc">-</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Recorded In System:</span>
                        <span class="detail-value text-muted" id="modalTxCreated">-</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeTxModal();">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
    // View Transaction Modal Script
    function openTxModal(data) {
        document.getElementById('modalTxId').textContent = '#' + data.id;
        document.getElementById('modalTxType').textContent = data.type;
        document.getElementById('modalTxAmount').textContent = data.amount;
        document.getElementById('modalTxCategory').textContent = data.category;
        document.getElementById('modalTxDate').textContent = data.date;
        document.getElementById('modalTxDesc').textContent = data.desc;
        document.getElementById('modalTxCreated').textContent = data.created;
        
        document.getElementById('txModal').classList.add('show');
    }

    function closeTxModal() {
        document.getElementById('txModal').classList.remove('show');
    }

    // Attach click listeners to all view buttons
    document.querySelectorAll('.view-tx-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            openTxModal({
                id: this.dataset.id,
                date: this.dataset.date,
                type: this.dataset.type,
                category: this.dataset.category,
                amount: this.dataset.amount,
                desc: this.dataset.desc,
                created: this.dataset.created
            });
        });
    });

    // Close modal on click outside content
    window.addEventListener('click', function(e) {
        const modal = document.getElementById('txModal');
        if (e.target === modal) {
            closeTxModal();
        }
    });
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>

