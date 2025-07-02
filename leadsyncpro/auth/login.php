<?php
require_once dirname(__DIR__) . '/config/config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: ' . APP_URL . '/dashboard/');
    exit();
}

$error_message = '';
$success_message = '';

// Check for timeout message
if (isset($_GET['timeout'])) {
    $error_message = 'Your session has expired. Please login again.';
}

// Check for logout message
if (isset($_GET['logout'])) {
    $success_message = 'You have been successfully logged out.';
}

// Handle login form submission
if ($_POST) {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error_message = 'Invalid security token. Please try again.';
    } else {
        $email = sanitizeInput($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember_me = isset($_POST['remember_me']);
        
        if (empty($email) || empty($password)) {
            $error_message = 'Please enter both email and password.';
        } else {
            $login_result = loginUser($email, $password);
            
            if ($login_result['success']) {
                // Handle remember me functionality
                if ($remember_me) {
                    setcookie('remember_email', $email, time() + (30 * 24 * 60 * 60), '/'); // 30 days
                }
                
                header('Location: ' . APP_URL . '/dashboard/');
                exit();
            } else {
                $error_message = $login_result['message'];
            }
        }
    }
}

// Pre-fill email if remembered
$remembered_email = $_COOKIE['remember_email'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo getSetting('company_name', 'LeadSync Pro'); ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="<?php echo APP_URL; ?>/assets/css/style.css" rel="stylesheet">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 450px;
            margin: 20px;
        }
        .logo-section {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo-section h1 {
            color: #667eea;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .logo-section p {
            color: #6c757d;
            margin: 0;
        }
        .form-floating {
            margin-bottom: 20px;
        }
        .btn-login {
            background: linear-gradient(45deg, #667eea, #764ba2);
            border: none;
            padding: 12px;
            font-weight: 600;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .divider {
            text-align: center;
            margin: 30px 0;
            position: relative;
        }
        .divider:before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: #dee2e6;
        }
        .divider span {
            background: rgba(255, 255, 255, 0.95);
            padding: 0 15px;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <!-- Logo Section -->
            <div class="logo-section">
                <h1><i class="fas fa-chart-line"></i> <?php echo getSetting('company_name', 'LeadSync Pro'); ?></h1>
                <p>Customer Relationship Management System</p>
            </div>
            
            <!-- Alert Messages -->
            <?php if ($error_message): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($success_message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- Login Form -->
            <form method="POST" class="needs-validation" novalidate>
                <?php echo getCSRFTokenField(); ?>
                
                <div class="form-floating">
                    <input type="email" class="form-control" id="email" name="email" 
                           placeholder="name@example.com" value="<?php echo htmlspecialchars($remembered_email); ?>" required>
                    <label for="email"><i class="fas fa-envelope"></i> Email Address</label>
                    <div class="invalid-feedback">
                        Please enter a valid email address.
                    </div>
                </div>
                
                <div class="form-floating">
                    <input type="password" class="form-control" id="password" name="password" 
                           placeholder="Password" required>
                    <label for="password"><i class="fas fa-lock"></i> Password</label>
                    <div class="invalid-feedback">
                        Please enter your password.
                    </div>
                </div>
                
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="remember_me" name="remember_me" 
                           <?php echo $remembered_email ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="remember_me">
                        Remember my email
                    </label>
                </div>
                
                <button type="submit" class="btn btn-primary btn-login w-100">
                    <i class="fas fa-sign-in-alt"></i> Sign In
                </button>
            </form>
            
            <!-- Divider -->
            <div class="divider">
                <span>Need Help?</span>
            </div>
            
            <!-- Additional Links -->
            <div class="text-center">
                <a href="forgot_password.php" class="text-decoration-none">
                    <i class="fas fa-key"></i> Forgot Password?
                </a>
            </div>
            
            <?php if (hasPermission('admin', 'guest')): ?>
            <div class="text-center mt-3">
                <a href="register.php" class="text-decoration-none">
                    <i class="fas fa-user-plus"></i> Create New Account
                </a>
            </div>
            <?php endif; ?>
            
            <!-- Demo Credentials (Remove in production) -->
            <div class="mt-4 p-3 bg-light rounded">
                <small class="text-muted">
                    <strong>Demo Credentials:</strong><br>
                    Email: admin@leadsyncpro.com<br>
                    Password: password (default - change after first login)
                </small>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Form validation
        (function() {
            'use strict';
            window.addEventListener('load', function() {
                var forms = document.getElementsByClassName('needs-validation');
                var validation = Array.prototype.filter.call(forms, function(form) {
                    form.addEventListener('submit', function(event) {
                        if (form.checkValidity() === false) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                        form.classList.add('was-validated');
                    }, false);
                });
            }, false);
        })();
        
        // Auto-dismiss alerts after 5 seconds
        setTimeout(function() {
            var alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                var bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
        
        // Focus on email field on page load
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('email').focus();
        });
    </script>
</body>
</html>