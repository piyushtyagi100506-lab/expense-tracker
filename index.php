<?php
/**
 * Application Entry Point & Router
 * 
 * Part of: Personal Expense Tracker Web Application
 * Web Application Development (WAD) Course Project
 * 
 * Explanatory Notes for Viva:
 * - Directs authenticated users immediately to their analytical dashboard.
 * - Redirects guests / new visitors to the login authentication gateway.
 */

require_once __DIR__ . '/includes/functions.php';

if (is_logged_in()) {
    redirect('pages/dashboard.php');
} else {
    redirect('auth/login.php');
}

