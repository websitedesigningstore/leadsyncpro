<?php
// Include configuration and check authentication
require_once dirname(__DIR__) . '/config/config.php';
requireLogin();
checkSessionTimeout();

// Get current user info
$current_user = getUserById($_SESSION['user_id']);
$unread_notifications = getUnreadNotificationsCount($_SESSION['user_id']);

// Get page title from global variable or set default
$page_title = isset($GLOBALS['page_title']) ? $GLOBALS['page_title'] : 'Dashboard';
$page_description = isset($GLOBALS['page_description']) ? $GLOBALS['page_description'] : '';

// Check for flash message
$flash_message = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo htmlspecialchars($page_description); ?>">
    <meta name="author" content="LeadSync Pro">
    <meta name="csrf-token" content="<?php echo generateCSRFToken(); ?>">
    <title><?php echo htmlspecialchars($page_title . ' - ' . getSetting('company_name', 'LeadSync Pro')); ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Custom CSS -->
    <link href="<?php echo APP_URL; ?>/assets/css/style.css" rel="stylesheet">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?php echo APP_URL; ?>/assets/images/favicon.ico">
    
    <style>
        :root {
            --primary-color: <?php echo getSetting('theme_color', '#007bff'); ?>;
        }
    </style>
</head>
<body>
    <?php if ($flash_message): ?>
        <div class="flash-message d-none" data-type="<?php echo $flash_message['type']; ?>">
            <?php echo htmlspecialchars($flash_message['message']); ?>
        </div>
    <?php endif; ?>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse" id="sidebar">
                <div class="position-sticky pt-3">
                    <!-- Logo -->
                    <div class="text-center mb-4">
                        <h3 class="text-white fw-bold"><?php echo getSetting('company_name', 'LeadSync Pro'); ?></h3>
                        <p class="text-white-50 small mb-0">CRM System</p>
                    </div>
                    
                    <!-- Navigation Menu -->
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo APP_URL; ?>/dashboard/">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo APP_URL; ?>/leads/">
                                <i class="fas fa-users"></i> Leads
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo APP_URL; ?>/leads/add_lead.php">
                                <i class="fas fa-user-plus"></i> Add Lead
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo APP_URL; ?>/modules/templates/">
                                <i class="fas fa-comments"></i> Communication
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo APP_URL; ?>/modules/dsr/">
                                <i class="fas fa-chart-line"></i> Daily Reports
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo APP_URL; ?>/modules/payments/">
                                <i class="fas fa-credit-card"></i> Payments
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo APP_URL; ?>/modules/analytics/">
                                <i class="fas fa-chart-bar"></i> Analytics
                            </a>
                        </li>
                        
                        <?php if (hasPermission('admin')): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo APP_URL; ?>/users/">
                                <i class="fas fa-users-cog"></i> User Management
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo APP_URL; ?>/modules/settings/">
                                <i class="fas fa-cog"></i> Settings
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo APP_URL; ?>/api/">
                                <i class="fas fa-code"></i> API Management
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo APP_URL; ?>/modules/reminders/">
                                <i class="fas fa-bell"></i> Reminders
                            </a>
                        </li>
                    </ul>
                    
                    <!-- User Profile Section -->
                    <div class="mt-auto pt-4">
                        <div class="border-top pt-3">
                            <div class="d-flex align-items-center text-white">
                                <div class="flex-shrink-0">
                                    <?php if ($current_user['profile_image']): ?>
                                        <img src="<?php echo APP_URL . '/assets/uploads/users/' . $current_user['profile_image']; ?>" 
                                             alt="Profile" class="rounded-circle" width="40" height="40">
                                    <?php else: ?>
                                        <div class="bg-light rounded-circle d-flex align-items-center justify-content-center" 
                                             style="width: 40px; height: 40px;">
                                            <i class="fas fa-user text-dark"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="mb-0"><?php echo htmlspecialchars($current_user['full_name']); ?></h6>
                                    <small class="text-white-50"><?php echo ucfirst($current_user['role']); ?></small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <!-- Top Navigation Bar -->
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <div class="d-flex align-items-center">
                        <button class="btn btn-outline-secondary d-md-none me-3 sidebar-toggle" type="button">
                            <i class="fas fa-bars"></i>
                        </button>
                        <h1 class="h2 mb-0"><?php echo htmlspecialchars($page_title); ?></h1>
                    </div>
                    
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <!-- Notification Bell -->
                            <div class="dropdown">
                                <button class="btn btn-outline-secondary position-relative" type="button" 
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-bell"></i>
                                    <?php if ($unread_notifications > 0): ?>
                                        <span class="notification-badge"><?php echo $unread_notifications > 99 ? '99+' : $unread_notifications; ?></span>
                                    <?php endif; ?>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end" style="min-width: 300px;">
                                    <li><h6 class="dropdown-header">Notifications</h6></li>
                                    <li><hr class="dropdown-divider"></li>
                                    
                                    <?php 
                                    $recent_notifications = getRecentNotifications($_SESSION['user_id'], 5);
                                    if (empty($recent_notifications)):
                                    ?>
                                        <li><span class="dropdown-item-text text-muted">No notifications</span></li>
                                    <?php else: ?>
                                        <?php foreach ($recent_notifications as $notification): ?>
                                            <li>
                                                <a class="dropdown-item <?php echo $notification['is_read'] ? '' : 'fw-bold'; ?>" href="#">
                                                    <div class="d-flex w-100 justify-content-between">
                                                        <small><?php echo htmlspecialchars($notification['title']); ?></small>
                                                        <small class="text-muted"><?php echo timeAgo($notification['created_at']); ?></small>
                                                    </div>
                                                    <small class="text-muted"><?php echo htmlspecialchars(substr($notification['message'], 0, 50)) . '...'; ?></small>
                                                </a>
                                            </li>
                                        <?php endforeach; ?>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item text-center" href="<?php echo APP_URL; ?>/modules/reminders/">View All</a></li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                            
                            <!-- User Dropdown -->
                            <div class="dropdown">
                                <button class="btn btn-outline-secondary dropdown-toggle" type="button" 
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-user"></i> <?php echo htmlspecialchars($current_user['full_name']); ?>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/users/profile.php">
                                        <i class="fas fa-user-edit"></i> Profile
                                    </a></li>
                                    <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/users/change_password.php">
                                        <i class="fas fa-lock"></i> Change Password
                                    </a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/auth/logout.php">
                                        <i class="fas fa-sign-out-alt"></i> Logout
                                    </a></li>
                                </ul>
                            </div>
                        </div>
                        
                        <!-- Quick Actions -->
                        <div class="btn-group">
                            <button type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown">
                                <i class="fas fa-plus"></i> Quick Add
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/leads/add_lead.php">
                                    <i class="fas fa-user-plus"></i> New Lead
                                </a></li>
                                <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/modules/payments/add_payment.php">
                                    <i class="fas fa-credit-card"></i> Add Payment
                                </a></li>
                                <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/modules/dsr/submit_dsr.php">
                                    <i class="fas fa-chart-line"></i> Daily Report
                                </a></li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Page Content -->
                <div class="main-content"><?php // Content will be inserted here by individual pages ?>