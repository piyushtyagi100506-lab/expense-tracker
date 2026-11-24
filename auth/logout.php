<?php
/**
 * User Logout Controller
 * 
 * Part of: Personal Expense Tracker Web Application
 * Web Application Development (WAD) Course Project
 * 
 * Explanatory Notes for Viva:
 * - Completely purges server-side session data and invalidates client session cookies.
 * - Destroys session instance to prevent unauthorized session reuse.
 * - Initializes a clean transient session state to inform the user of successful logout.
 */

require_once __DIR__ . '/../includes/functions.php';

// Unset all session variables
$_SESSION = [];

// Delete the session cookie from the client browser
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), 
        '', 
        time() - 42000,
        $params["path"], 
        $params["domain"], 
        $params["secure"], 
        $params["httponly"]
    );
}

// Destroy session on the server
session_destroy();

// Start fresh session to deliver logout notice
session_start();
set_flash('info', 'You have been safely logged out. See you next time!');
redirect('auth/login.php');

