<?php
/**
 * Authentication Gatekeeper / Middleware
 * 
 * Part of: Personal Expense Tracker Web Application
 * Web Application Development (WAD) Course Project
 * 
 * Explanatory Notes for Viva:
 * - Included at the top of every protected page and action script.
 * - Verifies whether a valid user session exists.
 * - If unauthenticated, saves a warning flash message and redirects to the login screen.
 * - Mitigates unauthorized access and privilege escalation.
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/functions.php';

if (!is_logged_in()) {
    set_flash('warning', 'Session expired or not logged in. Please sign in to continue.');
    redirect('auth/login.php');
}

