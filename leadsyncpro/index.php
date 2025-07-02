<?php
require_once 'config/config.php';

// Check if user is logged in
if (isLoggedIn()) {
    // Redirect to dashboard
    header('Location: ' . APP_URL . '/dashboard/');
} else {
    // Redirect to login page
    header('Location: ' . APP_URL . '/auth/login.php');
}
exit();
?>