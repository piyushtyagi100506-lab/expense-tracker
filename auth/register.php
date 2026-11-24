<?php
/**
 * User Registration Controller & View
 * 
 * Part of: Personal Expense Tracker Web Application
 * Web Application Development (WAD) Course Project
 * 
 * Explanatory Notes for Viva:
 * - Implements password hashing via password_hash() using default BCrypt algorithm.
 * - Enforces server-side validation alongside client-side JavaScript validation.
 * - Checks for duplicate email accounts before inserting new records.
 * - Protects against CSRF and SQL Injection.
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Redirect if already authenticated
if (is_logged_in()) {
    redirect('pages/dashboard.php');
}

$errors = [];
$name   = '';
$email  = '';

// Process POST registration submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Verify CSRF Token
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Security validation failed (invalid CSRF token). Please try again.';
    } else {
        $name             = trim($_POST['name'] ?? '');
        $email            = trim($_POST['email'] ?? '');
        $password         = $_POST['password'] ?? '';
        $confirmPassword  = $_POST['confirm_password'] ?? '';

        // 2. Comprehensive Server-Side Validation
        if (empty($name)) {
            $errors[] = 'Please enter your full name.';
        } elseif (strlen($name) < 2 || strlen($name) > 100) {
            $errors[] = 'Full name must be between 2 and 100 characters.';
        }

        if (empty($email)) {
            $errors[] = 'Please enter your email address.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }

        if (empty($password)) {
            $errors[] = 'Please choose a password.';
        } elseif (strlen($password) < 6) {
            $errors[] = 'Password must be at least 6 characters long.';
        }

        if ($password !== $confirmPassword) {
            $errors[] = 'Password confirmation does not match.';
        }

        // 3. Check for existing user account
        if (empty($errors)) {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
            $stmt->execute([':email' => $email]);
            if ($stmt->fetch()) {
                $errors[] = 'An account with this email address already exists. Please sign in instead.';
            }
        }

        // 4. Create new user account if validation passed
        if (empty($errors)) {
            $passwordHash = password_hash($password, PASSWORD_BCRYPT);

            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, created_at) VALUES (:name, :email, :password, NOW())");
            $stmt->execute([
                ':name'     => $name,
                ':email'    => $email,
                ':password' => $passwordHash
            ]);

            $newUserId = (int)$pdo->lastInsertId();

            // Prevent session fixation and log in automatically
            session_regenerate_id(true);
            $_SESSION['user_id']    = $newUserId;
            $_SESSION['user_name']  = $name;
            $_SESSION['user_email'] = $email;

            set_flash('success', 'Registration successful! Welcome to your Personal Expense Tracker, ' . $name . '.');
            redirect('pages/dashboard.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account | Personal Expense Tracker</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <link rel="stylesheet" href="<?= e(app_url('assets/css/style.css')) ?>">
</head>
<body class="auth-body">
    <div class="auth-container">
        <div class="auth-card">
            <!-- Brand Header -->
            <div class="auth-brand">
                <div class="brand-icon-lg">
                    <i class="fa-solid fa-user-plus"></i>
                </div>
                <h2>Create Account</h2>
                <p class="auth-subtitle">Start tracking your income, expenses &amp; monthly budgets</p>
            </div>

            <!-- Server-Side Error Alert Box -->
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <div class="alert-icon"><i class="fa-solid fa-circle-exclamation"></i></div>
                    <div class="alert-text">
                        <ul class="mb-0" style="margin: 0; padding-left: 18px;">
                            <?php foreach ($errors as $err): ?>
                                <li><?= e($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Registration Form -->
            <form action="<?= e(app_url('auth/register.php')) ?>" method="POST" class="auth-form" id="registerForm" novalidate>
                <?= csrf_field() ?>

                <div class="form-group">
                    <label for="name" class="form-label">Full Name</label>
                    <div class="input-icon-wrap">
                        <i class="fa-regular fa-user input-icon"></i>
                        <input type="text" id="name" name="name" class="form-control" 
                               value="<?= e($name) ?>" placeholder="e.g. John Doe" required autofocus>
                    </div>
                    <div class="invalid-feedback" id="nameError"></div>
                </div>

                <div class="form-group">
                    <label for="email" class="form-label">Email Address</label>
                    <div class="input-icon-wrap">
                        <i class="fa-regular fa-envelope input-icon"></i>
                        <input type="email" id="email" name="email" class="form-control" 
                               value="<?= e($email) ?>" placeholder="name@example.com" required>
                    </div>
                    <div class="invalid-feedback" id="emailError"></div>
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-icon-wrap">
                        <i class="fa-solid fa-lock input-icon"></i>
                        <input type="password" id="password" name="password" class="form-control" 
                               placeholder="Minimum 6 characters" minlength="6" required>
                    </div>
                    <div class="invalid-feedback" id="passwordError"></div>
                    <span class="field-hint">Must contain at least 6 characters.</span>
                </div>

                <div class="form-group">
                    <label for="confirm_password" class="form-label">Confirm Password</label>
                    <div class="input-icon-wrap">
                        <i class="fa-solid fa-shield-halved input-icon"></i>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" 
                               placeholder="Re-type your password" required>
                    </div>
                    <div class="invalid-feedback" id="confirmPasswordError"></div>
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-lg" id="submitRegisterBtn">
                    <i class="fa-solid fa-check"></i>
                    <span>Register Account</span>
                </button>
            </form>

            <!-- Card Footer -->
            <div class="auth-footer">
                <p>Already have an account? <a href="<?= e(app_url('auth/login.php')) ?>">Sign in here</a></p>
            </div>
        </div>
        
        <div class="auth-bottom-note">
            Personal Expense Tracker &bull; Web Application Development Course Project
        </div>
    </div>

    <!-- Scripts -->
    <script src="<?= e(app_url('assets/js/main.js')) ?>"></script>
    <script src="<?= e(app_url('assets/js/validation.js')) ?>"></script>
</body>
</html>

