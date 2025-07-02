<?php
// LeadSync Pro CRM - Main Configuration File

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Error reporting for development (set to 0 for production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Application Configuration
define('APP_NAME', 'LeadSync Pro');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/leadsyncpro');
define('BASE_PATH', dirname(__DIR__));
define('UPLOAD_PATH', BASE_PATH . '/assets/uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB

// Database Configuration
require_once BASE_PATH . '/config/database.php';

// Time zone setting
date_default_timezone_set('Asia/Kolkata');

// Security settings
define('CSRF_TOKEN_LENGTH', 32);
define('SESSION_TIMEOUT', 3600); // 1 hour
define('PASSWORD_MIN_LENGTH', 8);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes

// File upload settings
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif']);
define('ALLOWED_DOCUMENT_TYPES', ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt']);
define('ALLOWED_FILE_TYPES', array_merge(ALLOWED_IMAGE_TYPES, ALLOWED_DOCUMENT_TYPES));

// Pagination settings
define('DEFAULT_RECORDS_PER_PAGE', 25);
define('MAX_RECORDS_PER_PAGE', 100);

// Email configuration (will be loaded from database)
$GLOBALS['email_config'] = [
    'smtp_host' => '',
    'smtp_port' => 587,
    'smtp_username' => '',
    'smtp_password' => '',
    'smtp_encryption' => 'tls'
];

// Load system settings from database
function loadSystemSettings() {
    try {
        $conn = getDBConnection();
        $stmt = $conn->prepare("SELECT setting_key, setting_value FROM system_settings WHERE is_public = 1");
        $stmt->execute();
        $settings = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        return $settings;
    } catch (Exception $e) {
        return [];
    }
}

// Global system settings
$GLOBALS['system_settings'] = loadSystemSettings();

// Helper function to get setting value
function getSetting($key, $default = '') {
    return isset($GLOBALS['system_settings'][$key]) ? $GLOBALS['system_settings'][$key] : $default;
}

// Auto-load functions
require_once BASE_PATH . '/includes/functions.php';
require_once BASE_PATH . '/includes/security.php';
?>