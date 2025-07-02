<?php
// LeadSync Pro CRM - Security Functions

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(CSRF_TOKEN_LENGTH));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Get CSRF token input field
 */
function getCSRFTokenField() {
    $token = generateCSRFToken();
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_role']);
}

/**
 * Require login
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /leadsyncpro/auth/login.php');
        exit();
    }
}

/**
 * Check session timeout
 */
function checkSessionTimeout() {
    if (isset($_SESSION['last_activity'])) {
        $inactive_time = time() - $_SESSION['last_activity'];
        if ($inactive_time > SESSION_TIMEOUT) {
            session_destroy();
            header('Location: /leadsyncpro/auth/login.php?timeout=1');
            exit();
        }
    }
    $_SESSION['last_activity'] = time();
}

/**
 * Hash password
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verify password
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Validate password strength
 */
function validatePasswordStrength($password) {
    $errors = [];
    
    if (strlen($password) < PASSWORD_MIN_LENGTH) {
        $errors[] = "Password must be at least " . PASSWORD_MIN_LENGTH . " characters long";
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter";
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Password must contain at least one lowercase letter";
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one number";
    }
    
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $errors[] = "Password must contain at least one special character";
    }
    
    return $errors;
}

/**
 * Login user
 */
function loginUser($email, $password) {
    try {
        $conn = getDBConnection();
        
        // Check for too many failed attempts
        $ip = $_SERVER['REMOTE_ADDR'];
        $stmt = $conn->prepare("SELECT COUNT(*) FROM user_activity WHERE ip_address = ? AND action = 'login_failed' AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)");
        $stmt->execute([$ip, LOGIN_LOCKOUT_TIME]);
        $failed_attempts = $stmt->fetchColumn();
        
        if ($failed_attempts >= MAX_LOGIN_ATTEMPTS) {
            return ['success' => false, 'message' => 'Too many failed login attempts. Please try again later.'];
        }
        
        // Get user by email
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1 AND is_deleted = 0");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user || !verifyPassword($password, $user['password_hash'])) {
            // Log failed attempt
            if ($user) {
                logUserActivity($user['id'], 'login_failed', 'Failed login attempt');
            } else {
                logUserActivity(0, 'login_failed', "Failed login attempt for email: $email");
            }
            return ['success' => false, 'message' => 'Invalid email or password'];
        }
        
        // Create session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['last_activity'] = time();
        
        // Update last login
        $stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$user['id']]);
        
        // Create session record
        $session_token = bin2hex(random_bytes(32));
        $expires_at = date('Y-m-d H:i:s', time() + SESSION_TIMEOUT);
        $stmt = $conn->prepare("INSERT INTO user_sessions (user_id, session_token, ip_address, user_agent, expires_at) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$user['id'], $session_token, $ip, $_SERVER['HTTP_USER_AGENT'], $expires_at]);
        
        $_SESSION['session_token'] = $session_token;
        
        // Log successful login
        logUserActivity($user['id'], 'login_success', 'User logged in successfully');
        
        return ['success' => true, 'user' => $user];
        
    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Login failed. Please try again.'];
    }
}

/**
 * Logout user
 */
function logoutUser() {
    if (isset($_SESSION['user_id'])) {
        logUserActivity($_SESSION['user_id'], 'logout', 'User logged out');
        
        // Remove session from database
        if (isset($_SESSION['session_token'])) {
            try {
                $conn = getDBConnection();
                $stmt = $conn->prepare("DELETE FROM user_sessions WHERE session_token = ?");
                $stmt->execute([$_SESSION['session_token']]);
            } catch (Exception $e) {
                error_log("Logout error: " . $e->getMessage());
            }
        }
    }
    
    session_destroy();
}

/**
 * Register user
 */
function registerUser($data) {
    try {
        $conn = getDBConnection();
        
        // Validate required fields
        $required_fields = ['username', 'email', 'password', 'full_name'];
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                return ['success' => false, 'message' => ucfirst($field) . ' is required'];
            }
        }
        
        // Validate email
        if (!isValidEmail($data['email'])) {
            return ['success' => false, 'message' => 'Invalid email address'];
        }
        
        // Validate password
        $password_errors = validatePasswordStrength($data['password']);
        if (!empty($password_errors)) {
            return ['success' => false, 'message' => implode('. ', $password_errors)];
        }
        
        // Check if username exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$data['username']]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Username already exists'];
        }
        
        // Check if email exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$data['email']]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Email already exists'];
        }
        
        // Hash password
        $password_hash = hashPassword($data['password']);
        
        // Insert user
        $stmt = $conn->prepare("INSERT INTO users (username, email, password_hash, full_name, phone, role) VALUES (?, ?, ?, ?, ?, ?)");
        $result = $stmt->execute([
            $data['username'],
            $data['email'],
            $password_hash,
            $data['full_name'],
            $data['phone'] ?? null,
            $data['role'] ?? 'staff'
        ]);
        
        if ($result) {
            $user_id = $conn->lastInsertId();
            logUserActivity($user_id, 'user_registered', 'New user registered');
            return ['success' => true, 'user_id' => $user_id];
        } else {
            return ['success' => false, 'message' => 'Failed to create user'];
        }
        
    } catch (Exception $e) {
        error_log("Registration error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Registration failed. Please try again.'];
    }
}

/**
 * Send password reset email
 */
function sendPasswordResetEmail($email) {
    try {
        $conn = getDBConnection();
        
        // Check if email exists
        $stmt = $conn->prepare("SELECT id, full_name FROM users WHERE email = ? AND is_active = 1 AND is_deleted = 0");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            return ['success' => false, 'message' => 'Email address not found'];
        }
        
        // Generate reset token
        $token = bin2hex(random_bytes(32));
        $expires_at = date('Y-m-d H:i:s', time() + 3600); // 1 hour
        
        // Store reset token
        $stmt = $conn->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
        $stmt->execute([$email, $token, $expires_at]);
        
        // Send email (implement email sending logic here)
        $reset_link = APP_URL . "/auth/reset_password.php?token=" . $token;
        
        // For now, just log the reset link
        error_log("Password reset link for $email: $reset_link");
        
        logUserActivity($user['id'], 'password_reset_requested', 'Password reset email sent');
        
        return ['success' => true, 'message' => 'Password reset email sent'];
        
    } catch (Exception $e) {
        error_log("Password reset error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to send reset email'];
    }
}

/**
 * Reset password with token
 */
function resetPasswordWithToken($token, $new_password) {
    try {
        $conn = getDBConnection();
        
        // Validate token
        $stmt = $conn->prepare("SELECT email FROM password_resets WHERE token = ? AND expires_at > NOW() AND used = 0");
        $stmt->execute([$token]);
        $reset = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$reset) {
            return ['success' => false, 'message' => 'Invalid or expired reset token'];
        }
        
        // Validate password
        $password_errors = validatePasswordStrength($new_password);
        if (!empty($password_errors)) {
            return ['success' => false, 'message' => implode('. ', $password_errors)];
        }
        
        // Update password
        $password_hash = hashPassword($new_password);
        $stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE email = ?");
        $result = $stmt->execute([$password_hash, $reset['email']]);
        
        if ($result) {
            // Mark token as used
            $stmt = $conn->prepare("UPDATE password_resets SET used = 1 WHERE token = ?");
            $stmt->execute([$token]);
            
            // Get user ID for logging
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$reset['email']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                logUserActivity($user['id'], 'password_reset_completed', 'Password reset completed');
            }
            
            return ['success' => true, 'message' => 'Password reset successfully'];
        } else {
            return ['success' => false, 'message' => 'Failed to reset password'];
        }
        
    } catch (Exception $e) {
        error_log("Password reset error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to reset password'];
    }
}

/**
 * Clean expired sessions
 */
function cleanExpiredSessions() {
    try {
        $conn = getDBConnection();
        $stmt = $conn->prepare("DELETE FROM user_sessions WHERE expires_at < NOW()");
        $stmt->execute();
    } catch (Exception $e) {
        error_log("Clean sessions error: " . $e->getMessage());
    }
}

/**
 * Clean expired password reset tokens
 */
function cleanExpiredPasswordResets() {
    try {
        $conn = getDBConnection();
        $stmt = $conn->prepare("DELETE FROM password_resets WHERE expires_at < NOW() OR used = 1");
        $stmt->execute();
    } catch (Exception $e) {
        error_log("Clean password resets error: " . $e->getMessage());
    }
}

/**
 * Require specific role
 */
function requireRole($required_role) {
    requireLogin();
    if (!hasPermission($required_role)) {
        header('HTTP/1.0 403 Forbidden');
        die('Access denied. Insufficient permissions.');
    }
}

/**
 * Sanitize file upload
 */
function sanitizeFileUpload($file) {
    // Get file extension
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    // Check if extension is allowed
    if (!in_array($file_extension, ALLOWED_FILE_TYPES)) {
        return false;
    }
    
    // Check file size
    if ($file['size'] > MAX_FILE_SIZE) {
        return false;
    }
    
    // Check MIME type
    $allowed_mime_types = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'txt' => 'text/plain'
    ];
    
    $expected_mime = $allowed_mime_types[$file_extension] ?? '';
    $actual_mime = mime_content_type($file['tmp_name']);
    
    if ($expected_mime && $actual_mime !== $expected_mime) {
        return false;
    }
    
    return true;
}

/**
 * Generate API token
 */
function generateAPIToken($user_id, $token_name, $permissions = [], $rate_limit = 1000, $expires_at = null) {
    try {
        $conn = getDBConnection();
        
        $token = bin2hex(random_bytes(32));
        $token_hash = hash('sha256', $token);
        
        $stmt = $conn->prepare("INSERT INTO api_tokens (user_id, token_name, token_hash, permissions, rate_limit, expires_at) VALUES (?, ?, ?, ?, ?, ?)");
        $result = $stmt->execute([
            $user_id,
            $token_name,
            $token_hash,
            json_encode($permissions),
            $rate_limit,
            $expires_at
        ]);
        
        if ($result) {
            return ['success' => true, 'token' => $token];
        } else {
            return ['success' => false, 'message' => 'Failed to generate token'];
        }
        
    } catch (Exception $e) {
        error_log("API token generation error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to generate token'];
    }
}

/**
 * Verify API token
 */
function verifyAPIToken($token) {
    try {
        $conn = getDBConnection();
        
        $token_hash = hash('sha256', $token);
        $stmt = $conn->prepare("SELECT * FROM api_tokens WHERE token_hash = ? AND is_active = 1 AND (expires_at IS NULL OR expires_at > NOW())");
        $stmt->execute([$token_hash]);
        $api_token = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($api_token) {
            // Update last used
            $stmt = $conn->prepare("UPDATE api_tokens SET last_used_at = NOW() WHERE id = ?");
            $stmt->execute([$api_token['id']]);
            
            return ['success' => true, 'token' => $api_token];
        } else {
            return ['success' => false, 'message' => 'Invalid or expired token'];
        }
        
    } catch (Exception $e) {
        error_log("API token verification error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Token verification failed'];
    }
}

/**
 * Rate limit check for API
 */
function checkAPIRateLimit($token_id) {
    try {
        $conn = getDBConnection();
        
        // Get token details
        $stmt = $conn->prepare("SELECT rate_limit FROM api_tokens WHERE id = ?");
        $stmt->execute([$token_id]);
        $token = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$token) {
            return false;
        }
        
        // Count requests in last hour
        $stmt = $conn->prepare("SELECT COUNT(*) FROM api_logs WHERE token_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
        $stmt->execute([$token_id]);
        $request_count = $stmt->fetchColumn();
        
        return $request_count < $token['rate_limit'];
        
    } catch (Exception $e) {
        error_log("Rate limit check error: " . $e->getMessage());
        return false;
    }
}
?>