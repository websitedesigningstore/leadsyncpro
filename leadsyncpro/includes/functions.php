<?php
// LeadSync Pro CRM - Core Helper Functions

/**
 * Sanitize input data
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Validate email address
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate phone number (basic validation)
 */
function isValidPhone($phone) {
    return preg_match('/^[0-9+\-\s\(\)]+$/', $phone);
}

/**
 * Generate unique lead number
 */
function generateLeadNumber() {
    $prefix = 'LD';
    $year = date('Y');
    $month = date('m');
    $randomNumber = rand(1000, 9999);
    return $prefix . $year . $month . $randomNumber;
}

/**
 * Format currency
 */
function formatCurrency($amount, $currency = 'INR') {
    $symbols = [
        'INR' => '₹',
        'USD' => '$',
        'EUR' => '€',
        'GBP' => '£'
    ];
    $symbol = isset($symbols[$currency]) ? $symbols[$currency] : $currency;
    return $symbol . number_format($amount, 2);
}

/**
 * Format date according to system setting
 */
function formatDate($date, $format = null) {
    if (empty($date) || $date == '0000-00-00' || $date == '0000-00-00 00:00:00') {
        return '-';
    }
    
    if ($format === null) {
        $format = getSetting('date_format', 'd-m-Y');
    }
    
    return date($format, strtotime($date));
}

/**
 * Format datetime
 */
function formatDateTime($datetime, $format = null) {
    if (empty($datetime) || $datetime == '0000-00-00 00:00:00') {
        return '-';
    }
    
    if ($format === null) {
        $format = getSetting('date_format', 'd-m-Y') . ' H:i';
    }
    
    return date($format, strtotime($datetime));
}

/**
 * Calculate time ago
 */
function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . ' minutes ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    if ($time < 31536000) return floor($time/2592000) . ' months ago';
    return floor($time/31536000) . ' years ago';
}

/**
 * Generate WhatsApp URL
 */
function generateWhatsAppURL($phone, $message = '') {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    if (substr($phone, 0, 1) !== '+') {
        $phone = '+91' . $phone; // Default to India country code
    }
    $encodedMessage = urlencode($message);
    return "https://wa.me/{$phone}?text={$encodedMessage}";
}

/**
 * Log user activity
 */
function logUserActivity($user_id, $action, $description = '', $ip = null, $user_agent = null) {
    try {
        $conn = getDBConnection();
        $ip = $ip ?: $_SERVER['REMOTE_ADDR'] ?? '';
        $user_agent = $user_agent ?: $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        $stmt = $conn->prepare("INSERT INTO user_activity (user_id, action, description, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $action, $description, $ip, $user_agent]);
    } catch (Exception $e) {
        error_log("Failed to log user activity: " . $e->getMessage());
    }
}

/**
 * Get user by ID
 */
function getUserById($user_id) {
    try {
        $conn = getDBConnection();
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND is_deleted = 0");
        $stmt->execute([$user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Get lead sources
 */
function getLeadSources() {
    try {
        $conn = getDBConnection();
        $stmt = $conn->prepare("SELECT * FROM lead_sources WHERE is_active = 1 ORDER BY name");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Get lead statuses
 */
function getLeadStatuses() {
    try {
        $conn = getDBConnection();
        $stmt = $conn->prepare("SELECT * FROM lead_statuses WHERE is_active = 1 ORDER BY name");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Get active users
 */
function getActiveUsers() {
    try {
        $conn = getDBConnection();
        $stmt = $conn->prepare("SELECT id, full_name, role FROM users WHERE is_active = 1 AND is_deleted = 0 ORDER BY full_name");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Get communication types
 */
function getCommunicationTypes() {
    try {
        $conn = getDBConnection();
        $stmt = $conn->prepare("SELECT * FROM communication_types WHERE is_active = 1 ORDER BY name");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Get payment types
 */
function getPaymentTypes() {
    try {
        $conn = getDBConnection();
        $stmt = $conn->prepare("SELECT * FROM payment_types WHERE is_active = 1 ORDER BY name");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Upload file
 */
function uploadFile($file, $upload_dir, $allowed_types = null) {
    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
        return ['success' => false, 'message' => 'No file uploaded'];
    }
    
    $allowed_types = $allowed_types ?: ALLOWED_FILE_TYPES;
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($file_extension, $allowed_types)) {
        return ['success' => false, 'message' => 'File type not allowed'];
    }
    
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => 'File size too large'];
    }
    
    $upload_path = UPLOAD_PATH . $upload_dir . '/';
    if (!is_dir($upload_path)) {
        mkdir($upload_path, 0777, true);
    }
    
    $filename = time() . '_' . uniqid() . '.' . $file_extension;
    $target_file = $upload_path . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        return [
            'success' => true,
            'filename' => $filename,
            'file_path' => $upload_dir . '/' . $filename,
            'file_size' => $file['size']
        ];
    } else {
        return ['success' => false, 'message' => 'Failed to upload file'];
    }
}

/**
 * Delete file
 */
function deleteFile($file_path) {
    $full_path = UPLOAD_PATH . $file_path;
    if (file_exists($full_path)) {
        return unlink($full_path);
    }
    return false;
}

/**
 * Send notification
 */
function sendNotification($user_id, $type_id, $title, $message, $related_type = null, $related_id = null) {
    try {
        $conn = getDBConnection();
        $stmt = $conn->prepare("INSERT INTO notifications (user_id, type_id, title, message, related_type, related_id) VALUES (?, ?, ?, ?, ?, ?)");
        return $stmt->execute([$user_id, $type_id, $title, $message, $related_type, $related_id]);
    } catch (Exception $e) {
        error_log("Failed to send notification: " . $e->getMessage());
        return false;
    }
}

/**
 * Get unread notifications count
 */
function getUnreadNotificationsCount($user_id) {
    try {
        $conn = getDBConnection();
        $stmt = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$user_id]);
        return $stmt->fetchColumn();
    } catch (Exception $e) {
        return 0;
    }
}

/**
 * Get recent notifications
 */
function getRecentNotifications($user_id, $limit = 10) {
    try {
        $conn = getDBConnection();
        $stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?");
        $stmt->execute([$user_id, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Generate random string
 */
function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

/**
 * Check if user has permission
 */
function hasPermission($required_role, $user_role = null) {
    if ($user_role === null) {
        $user_role = $_SESSION['user_role'] ?? '';
    }
    
    $role_hierarchy = [
        'admin' => 4,
        'manager' => 3,
        'staff' => 2,
        'agent' => 1
    ];
    
    $required_level = $role_hierarchy[$required_role] ?? 0;
    $user_level = $role_hierarchy[$user_role] ?? 0;
    
    return $user_level >= $required_level;
}

/**
 * Redirect with message
 */
function redirectWithMessage($url, $message, $type = 'success') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
    header("Location: $url");
    exit();
}

/**
 * Get and clear flash message
 */
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'] ?? 'info';
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);
        return ['message' => $message, 'type' => $type];
    }
    return null;
}

/**
 * Validate CSRF token
 */
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Generate pagination HTML
 */
function generatePagination($current_page, $total_pages, $base_url, $query_params = []) {
    if ($total_pages <= 1) return '';
    
    $html = '<nav aria-label="Page navigation"><ul class="pagination justify-content-center">';
    
    // Previous button
    if ($current_page > 1) {
        $query_params['page'] = $current_page - 1;
        $url = $base_url . '?' . http_build_query($query_params);
        $html .= '<li class="page-item"><a class="page-link" href="' . $url . '">&laquo; Previous</a></li>';
    }
    
    // Page numbers
    $start = max(1, $current_page - 2);
    $end = min($total_pages, $current_page + 2);
    
    for ($i = $start; $i <= $end; $i++) {
        $query_params['page'] = $i;
        $url = $base_url . '?' . http_build_query($query_params);
        $active = $i == $current_page ? ' active' : '';
        $html .= '<li class="page-item' . $active . '"><a class="page-link" href="' . $url . '">' . $i . '</a></li>';
    }
    
    // Next button
    if ($current_page < $total_pages) {
        $query_params['page'] = $current_page + 1;
        $url = $base_url . '?' . http_build_query($query_params);
        $html .= '<li class="page-item"><a class="page-link" href="' . $url . '">Next &raquo;</a></li>';
    }
    
    $html .= '</ul></nav>';
    return $html;
}
?>