<?php
/**
 * User Login Controller & View
 * 
 * Part of: Personal Expense Tracker Web Application
 * Web Application Development (WAD) Course Project
 * 
 * Explanatory Notes for Viva:
 * - Implements secure credential verification using password_verify().
 * - Calls session_regenerate_id(true) on successful login to prevent Session Fixation attacks.
 * - Restores user preferences (Currency symbol, Dark/Light mode theme).
 * - Enforces CSRF token validation on POST submission.
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (is_logged_in()) {
    redirect('pages/dashboard.php');
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Security validation failed (invalid CSRF token). Please try again.';
    } else {
        $email    = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (empty($email) || empty($password)) {
            $error = 'Please enter both your email and password.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            $stmt = $pdo->prepare("SELECT id, name, email, password, currency, theme FROM users WHERE email = :email LIMIT 1");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                session_regenerate_id(true);

                $_SESSION['user_id']       = (int)$user['id'];
                $_SESSION['user_name']     = $user['name'];
                $_SESSION['user_email']    = $user['email'];
                $_SESSION['user_currency'] = !empty($user['currency']) ? $user['currency'] : '$';
                $_SESSION['user_theme']    = !empty($user['theme']) ? $user['theme'] : 'light';

                set_flash('success', 'Welcome back, ' . $user['name'] . '! You have successfully logged in.');
                redirect('pages/dashboard.php');
            } else {
                $error = 'Invalid email address or password. Please try again.';
            }
        }
    }
}

$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In | Personal Expense Tracker</title>
    
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
                    <i class="fa-solid fa-wallet"></i>
                </div>
                <h2>ExpenseTracker</h2>
                <p class="auth-subtitle">Sign in to manage your income, expenses &amp; savings goals</p>
            </div>

            <!-- Flash Notification -->
            <?php if ($flash): ?>
                <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible">
                    <div class="alert-icon"><i class="fa-solid fa-circle-info"></i></div>
                    <div class="alert-text"><?= e($flash['message']) ?></div>
                    <button type="button" class="alert-close" onclick="this.parentElement.remove();">&times;</button>
                </div>
            <?php endif; ?>

            <!-- Error Banner -->
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <div class="alert-icon"><i class="fa-solid fa-circle-exclamation"></i></div>
                    <div class="alert-text"><?= e($error) ?></div>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form action="<?= e(app_url('auth/login.php')) ?>" method="POST" class="auth-form" id="loginForm" novalidate>
                <?= csrf_field() ?>

                <div class="form-group">
                    <label for="email" class="form-label">Email Address</label>
                    <div class="input-icon-wrap">
                        <i class="fa-regular fa-envelope input-icon"></i>
                        <input type="email" id="email" name="email" class="form-control" 
                               value="<?= e($email) ?>" placeholder="name@example.com" required autofocus>
                    </div>
                    <div class="invalid-feedback" id="emailError"></div>
                </div>

                <div class="form-group">
                    <div class="label-row">
                        <label for="password" class="form-label">Password</label>
                    </div>
                    <div class="input-icon-wrap">
                        <i class="fa-solid fa-lock input-icon"></i>
                        <input type="password" id="password" name="password" class="form-control" 
                               placeholder="Enter your password" required>
                        <button type="button" class="password-toggle-btn" id="togglePassword" aria-label="Toggle password visibility">
                            <i class="fa-regular fa-eye"></i>
                        </button>
                    </div>
                    <div class="invalid-feedback" id="passwordError"></div>
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-lg" id="submitLoginBtn">
                    <i class="fa-solid fa-right-to-bracket"></i>
                    <span>Sign In</span>
                </button>
            </form>

            <!-- Quick Demo Credentials Box -->
            <div class="demo-box">
                <div class="demo-header">
                    <i class="fa-solid fa-graduation-cap"></i>
                    <span>WAD Evaluation Quick Fill</span>
                </div>
                <p class="demo-desc">Use the pre-seeded student account for fast testing:</p>
                <div class="demo-creds">
                    <code>demo@example.com</code> / <code>password123</code>
                </div>
                <button type="button" class="btn btn-secondary btn-sm btn-block" id="fillDemoBtn">
                    <i class="fa-solid fa-bolt"></i> Auto-Fill Demo Account
                </button>
            </div>

            <!-- Card Footer -->
            <div class="auth-footer">
                <p>Don't have an account yet? <a href="<?= e(app_url('auth/register.php')) ?>">Create account</a></p>
            </div>
        </div>
        
        <div class="auth-bottom-note">
            Personal Expense Tracker &bull; Web Application Development Course Project
        </div>
    </div>

    <!-- Scripts -->
    <script src="<?= e(app_url('assets/js/main.js')) ?>"></script>
    <script src="<?= e(app_url('assets/js/validation.js')) ?>"></script>
    <script>
        document.getElementById('fillDemoBtn')?.addEventListener('click', function() {
            document.getElementById('email').value = 'demo@example.com';
            document.getElementById('password').value = 'password123';
            const feedbackEls = document.querySelectorAll('.invalid-feedback');
            feedbackEls.forEach(el => el.textContent = '');
        });
    </script>
</body>
</html>
