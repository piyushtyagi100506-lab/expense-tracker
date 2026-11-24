<?php
/**
 * Database Configuration & Connection (PDO)
 * 
 * Part of: Personal Expense Tracker Web Application
 * Web Application Development (WAD) Course Project
 * 
 * Explanatory Notes for Viva:
 * - Uses PHP Data Objects (PDO) instead of mysqli for portability and prepared statement security.
 * - Disables emulated prepared statements to ensure queries are truly parameterized at the database level.
 * - Sets default fetch mode to associative arrays (PDO::FETCH_ASSOC).
 */

// Database server credentials (works with local MariaDB/MySQL installs on macOS)
$dbUser = getenv('EXPENSE_TRACKER_DB_USER') ?: (getenv('USER') ?: 'root');
$dbPass = getenv('EXPENSE_TRACKER_DB_PASS') ?: '';

define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'expense_tracker');
define('DB_USER', $dbUser);
define('DB_PASS', $dbPass);
define('DB_CHARSET', 'utf8mb4');

try {
    // Data Source Name (DSN) string
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;

    // PDO options for security and error resilience
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Throw exceptions on errors
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Return associative arrays by default
        PDO::ATTR_EMULATE_PREPARES   => false,                  // Native prepared statements prevent SQL injection
    ];

    // Instantiate the PDO instance
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

} catch (PDOException $e) {
    // Student-friendly error display when database is not yet imported or MySQL service is down
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Database Connection Error - Expense Tracker</title>
        <style>
            body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f8fafc; color: #1e293b; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; padding: 20px; box-sizing: border-box; }
            .error-card { background: #ffffff; border: 1px solid #e2e8f0; border-top: 5px solid #ef4444; border-radius: 12px; padding: 30px; max-width: 650px; width: 100%; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.08); }
            h2 { color: #dc2626; margin-top: 0; font-size: 1.4rem; display: flex; align-items: center; gap: 10px; }
            p { line-height: 1.6; color: #475569; font-size: 0.95rem; }
            .code-box { background: #0f172a; color: #38bdf8; padding: 12px 16px; border-radius: 8px; font-family: monospace; font-size: 0.85rem; overflow-x: auto; margin: 15px 0; }
            ol { padding-left: 20px; line-height: 1.8; color: #334155; }
            li strong { color: #0f172a; }
            .btn { display: inline-block; background: #0d9488; color: white; padding: 10px 18px; border-radius: 6px; text-decoration: none; font-weight: 500; margin-top: 15px; }
            .btn:hover { background: #0f766e; }
        </style>
    </head>
    <body>
        <div class="error-card">
            <h2>Database Connection Failed</h2>
            <p>Could not connect to the database <strong><?= htmlspecialchars(DB_NAME) ?></strong> on <strong><?= htmlspecialchars(DB_HOST) ?></strong>.</p>
            
            <div class="code-box"><?= htmlspecialchars($e->getMessage()) ?></div>

            <p><strong>Troubleshooting Steps for XAMPP / WAMP:</strong></p>
            <ol>
                <li>Open <strong>XAMPP Control Panel</strong> and verify that the <strong>MySQL</strong> module is running (Status: Green).</li>
                <li>Open <strong>phpMyAdmin</strong> (<a href="http://localhost/phpmyadmin" target="_blank">http://localhost/phpmyadmin</a>).</li>
                <li>Click <strong>Import</strong> tab and upload the file: <code>database.sql</code> from this project folder.</li>
                <li>If your MySQL root password is not empty, update <code>config/db.php</code> with your password.</li>
            </ol>
            <a href="javascript:location.reload();" class="btn">Retry Connection</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

