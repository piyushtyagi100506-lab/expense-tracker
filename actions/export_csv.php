<?php
/**
 * Action Handler: Export Transactions to CSV
 * 
 * Part of: Personal Expense Tracker Web Application
 * Web Application Development (WAD) Course Project
 * 
 * Explanatory Notes for Viva:
 * - Directs database records directly into php://output stream using fputcsv().
 * - Prepends a UTF-8 Byte Order Mark (BOM) to ensure clean rendering in Microsoft Excel.
 * - Respects active filter parameters (search keyword, date ranges, types, and months).
 * - Enforces user isolation so only the authenticated user's records are extracted.
 */

require_once __DIR__ . '/../includes/auth_check.php';

$userId = get_user_id();

// Capture filter options
$searchKeyword = trim($_GET['q'] ?? '');
$filterType    = trim($_GET['type'] ?? 'all');
$filterCategory= (int)($_GET['category'] ?? 0);
$fromDate      = trim($_GET['from_date'] ?? '');
$toDate        = trim($_GET['to_date'] ?? '');
$filterMonth   = (int)($_GET['month'] ?? 0);
$filterYear    = (int)($_GET['year'] ?? 0);

// Build dynamic WHERE clause
$whereClauses = ["t.user_id = :user_id"];
$params = [':user_id' => $userId];

if ($searchKeyword !== '') {
    $whereClauses[] = "(t.description LIKE :search OR c.name LIKE :search)";
    $params[':search'] = '%' . $searchKeyword . '%';
}

if (in_array($filterType, ['income', 'expense'], true)) {
    $whereClauses[] = "t.type = :type";
    $params[':type'] = $filterType;
}

if ($filterCategory > 0) {
    $whereClauses[] = "t.category_id = :cat_id";
    $params[':cat_id'] = $filterCategory;
}

if (!empty($fromDate)) {
    $whereClauses[] = "t.transaction_date >= :from_date";
    $params[':from_date'] = $fromDate;
}

if (!empty($toDate)) {
    $whereClauses[] = "t.transaction_date <= :to_date";
    $params[':to_date'] = $toDate;
}

if ($filterMonth > 0) {
    $whereClauses[] = "MONTH(t.transaction_date) = :m";
    $params[':m'] = $filterMonth;
}

if ($filterYear > 0) {
    $whereClauses[] = "YEAR(t.transaction_date) = :y";
    $params[':y'] = $filterYear;
}

$whereSql = "WHERE " . implode(" AND ", $whereClauses);

// Fetch records
$sql = "
    SELECT 
        t.id, 
        t.transaction_date, 
        t.type, 
        c.name AS category_name, 
        t.description, 
        t.amount, 
        t.created_at
    FROM transactions t
    JOIN categories c ON t.category_id = c.id
    $whereSql
    ORDER BY t.transaction_date DESC, t.id DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

// Set HTTP headers for file download
$filename = "expense_tracker_export_" . date('Y-m-d_His') . ".csv";

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// Open standard output stream
$output = fopen('php://output', 'w');

// Write UTF-8 BOM for Microsoft Excel compatibility
fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

// Write CSV Header Row
fputcsv($output, [
    'Transaction ID',
    'Date',
    'Type',
    'Category',
    'Description',
    'Amount (' . CURRENCY_SYMBOL . ')',
    'Created At'
]);

// Write Data Rows
foreach ($rows as $row) {
    fputcsv($output, [
        $row['id'],
        $row['transaction_date'],
        ucfirst($row['type']),
        $row['category_name'],
        $row['description'] ?? '',
        number_format((float)$row['amount'], 2, '.', ''),
        $row['created_at']
    ]);
}

fclose($output);
exit;

