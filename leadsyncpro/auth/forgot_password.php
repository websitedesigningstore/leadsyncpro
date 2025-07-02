<?php
require_once dirname(__DIR__) . '/config/config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: ' . APP_URL . '/dashboard/');
    exit();
}

$message = '';
$message_type = '';

// Handle form submission
if ($_POST) {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid security token. Please try again.';
        $message_type = 'danger';
    } else {
        $email = sanitizeInput($_POST['email'] ?? '');
        
        if (empty($email)) {
            $message = 'Please enter your email address.';
            $message_type = 'danger';
        } elseif (!isValidEmail($email)) {
            $message = 'Please enter a valid email address.';
            $message_type = 'danger';
        } else {
            $reset_result = sendPasswordResetEmail($email);
            
            if ($reset_result['success']) {
                $message = 'Password reset instructions have been sent to your email address.';
                $message_type = 'success';
            } else {
                $message = $reset_result['message'];
                $message_type = 'danger';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - <?php echo getSetting('company_name', 'LeadSync Pro'); ?></title>
    
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
        .btn-reset {
            background: linear-gradient(45deg, #667eea, #764ba2);
            border: none;
            padding: 12px;
            font-weight: 600;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        .btn-reset:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <!-- Logo Section -->
            <div class="logo-section">
                <h1><i class="fas fa-key"></i> Forgot Password</h1>
                <p>Enter your email to reset your password</p>
            </div>
            
            <!-- Alert Messages -->
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                    <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- Reset Form -->
            <form method="POST" class="needs-validation" novalidate>
                <?php echo getCSRFTokenField(); ?>
                
                <div class="form-floating">
                    <input type="email" class="form-control" id="email" name="email" 
                           placeholder="name@example.com" required>
                    <label for="email"><i class="fas fa-envelope"></i> Email Address</label>
                    <div class="invalid-feedback">
                        Please enter a valid email address.
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary btn-reset w-100">
                    <i class="fas fa-paper-plane"></i> Send Reset Link
                </button>
            </form>
            
            <!-- Back to Login -->
            <div class="text-center mt-4">
                <a href="login.php" class="text-decoration-none">
                    <i class="fas fa-arrow-left"></i> Back to Login
                </a>
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
        
        // Focus on email field on page load
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('email').focus();
        });
    </script>
</body>
</html>