<?php
require_once dirname(__DIR__) . '/config/config.php';

// Logout user
logoutUser();

// Redirect to login page with logout message
header('Location: ' . APP_URL . '/auth/login.php?logout=1');
exit();
?>