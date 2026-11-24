<?php
/**
 * Global Helper Functions & Session Initialization
 * 
 * Part of: Personal Expense Tracker Web Application
 * Web Application Development (WAD) Course Project
 * 
 * Explanatory Notes for Viva:
 * - Session Management: Configures HTTP-only and SameSite cookies.
 * - Dynamic Currency: Supports user-configured currency symbols ($, ₹, €, £, ¥, etc.) stored in session.
 * - CSRF & XSS: Cryptographic token validation with hash_equals() and HTML entity output sanitization.
 * - Theme Preference: Manages Light/Dark theme persistence.
 */

// Start session if not already active
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

/**
 * Returns user's selected currency symbol or default '$'.
 */
function get_currency_symbol(): string {
    return $_SESSION['user_currency'] ?? '$';
}

/**
 * Returns user's selected theme ('light' or 'dark').
 */
function get_user_theme(): string {
    return $_SESSION['user_theme'] ?? 'light';
}

/**
 * Returns physical filesystem path from project root.
 */
function app_path(string $path = ''): string {
    return dirname(__DIR__) . ($path ? '/' . ltrim($path, '/') : '');
}

/**
 * Dynamically resolves application web URL path.
 */
function app_url(string $path = ''): string {
    static $baseUrl = null;
    if ($baseUrl === null) {
        $docRoot = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? ''));
        $appRoot = str_replace('\\', '/', dirname(__DIR__));

        if (!empty($docRoot) && strpos($appRoot, $docRoot) === 0) {
            $relative = substr($appRoot, strlen($docRoot));
            $baseUrl = rtrim($relative, '/') . '/';
        } else {
            $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
            $parts = explode('/', trim($script, '/'));
            $pos = array_search('expense-tracker', $parts);
            if ($pos !== false) {
                $baseUrl = '/' . implode('/', array_slice($parts, 0, $pos + 1)) . '/';
            } else {
                $baseUrl = '/';
            }
        }
    }
    return rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
}

/**
 * Safe output escaping against Cross-Site Scripting (XSS).
 */
function e($value): string {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Formats a decimal amount as currency with the active user currency symbol.
 */
function format_currency($amount, ?string $symbol = null): string {
    $sym = $symbol ?? get_currency_symbol();
    $num = (float)$amount;
    return $sym . number_format($num, 2);
}

/**
 * Formats a standard MySQL date (YYYY-MM-DD).
 */
function format_date(string $date, string $format = 'M d, Y'): string {
    if (empty($date) || $date === '0000-00-00') {
        return '-';
    }
    $timestamp = strtotime($date);
    return $timestamp ? date($format, $timestamp) : $date;
}

/**
 * Generates or retrieves the session CSRF token.
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Renders an HTML hidden input tag containing the CSRF token.
 */
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/**
 * Verifies that the submitted CSRF token matches the session token.
 */
function verify_csrf_token(?string $token): bool {
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Checks whether a user is currently logged in.
 */
function is_logged_in(): bool {
    return !empty($_SESSION['user_id']);
}

function get_user_id(): int {
    return (int)($_SESSION['user_id'] ?? 0);
}

function get_user_name(): string {
    return $_SESSION['user_name'] ?? 'User';
}

function get_user_email(): string {
    return $_SESSION['user_email'] ?? '';
}

/**
 * Sets a flash message to be displayed once on the next page view.
 */
function set_flash(string $type, string $message): void {
    $_SESSION['flash'] = [
        'type'    => $type,
        'message' => $message
    ];
}

/**
 * Retrieves and clears any active flash message.
 */
function get_flash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Redirects the browser to an internal path.
 */
function redirect(string $path): void {
    if (!preg_match('~^(?:f|ht)tps?://~i', $path) && strpos($path, '/') !== 0) {
        $path = app_url($path);
    }
    header("Location: " . $path);
    exit;
}

/**
 * Fetches categories from the database (system defaults + user customs).
 */
function get_categories(PDO $pdo, ?string $type = null, ?int $userId = null): array {
    $userId = $userId ?? get_user_id();
    
    $sql = "SELECT id, name, type, user_id 
            FROM categories 
            WHERE (user_id IS NULL OR user_id = :user_id)";
    
    $params = [':user_id' => $userId];

    if ($type !== null && in_array($type, ['income', 'expense'], true)) {
        $sql .= " AND type = :type";
        $params[':type'] = $type;
    }

    $sql .= " ORDER BY (user_id IS NOT NULL) DESC, type ASC, name ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}
