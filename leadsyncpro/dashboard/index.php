<?php
// Set page title and description
$GLOBALS['page_title'] = 'Dashboard';
$GLOBALS['page_description'] = 'Overview of your CRM activities and performance metrics';

// Include header
include_once dirname(__DIR__) . '/includes/header.php';

// Get dashboard data
try {
    $conn = getDBConnection();
    
    // Today's stats
    $today = date('Y-m-d');
    $this_month = date('Y-m');
    
    // Total leads
    $stmt = $conn->prepare("SELECT COUNT(*) FROM leads WHERE is_deleted = 0");
    $stmt->execute();
    $total_leads = $stmt->fetchColumn();
    
    // Today's leads
    $stmt = $conn->prepare("SELECT COUNT(*) FROM leads WHERE DATE(created_at) = ? AND is_deleted = 0");
    $stmt->execute([$today]);
    $today_leads = $stmt->fetchColumn();
    
    // This month's leads
    $stmt = $conn->prepare("SELECT COUNT(*) FROM leads WHERE DATE_FORMAT(created_at, '%Y-%m') = ? AND is_deleted = 0");
    $stmt->execute([$this_month]);
    $month_leads = $stmt->fetchColumn();
    
    // Converted leads
    $stmt = $conn->prepare("SELECT COUNT(*) FROM leads WHERE is_converted = 1 AND is_deleted = 0");
    $stmt->execute();
    $converted_leads = $stmt->fetchColumn();
    
    // Follow-ups due today
    $stmt = $conn->prepare("SELECT COUNT(*) FROM leads WHERE DATE(follow_up_date) = ? AND is_deleted = 0");
    $stmt->execute([$today]);
    $followups_today = $stmt->fetchColumn();
    
    // Overdue follow-ups
    $stmt = $conn->prepare("SELECT COUNT(*) FROM leads WHERE follow_up_date < NOW() AND follow_up_date IS NOT NULL AND is_converted = 0 AND is_deleted = 0");
    $stmt->execute();
    $overdue_followups = $stmt->fetchColumn();
    
    // Total payments this month
    $stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE DATE_FORMAT(payment_date, '%Y-%m') = ? AND is_deleted = 0");
    $stmt->execute([$this_month]);
    $month_payments = $stmt->fetchColumn();
    
    // Pending payments
    $stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status IN ('pending', 'partially_paid') AND is_deleted = 0");
    $stmt->execute();
    $pending_payments = $stmt->fetchColumn();
    
    // Recent leads
    $stmt = $conn->prepare("
        SELECT l.*, ls.name as source_name, lst.name as status_name, u.full_name as assigned_name
        FROM leads l
        LEFT JOIN lead_sources ls ON l.lead_source_id = ls.id
        LEFT JOIN lead_statuses lst ON l.lead_status_id = lst.id
        LEFT JOIN users u ON l.assigned_to = u.id
        WHERE l.is_deleted = 0
        ORDER BY l.created_at DESC
        LIMIT 10
    ");
    $stmt->execute();
    $recent_leads = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Lead status distribution for chart
    $stmt = $conn->prepare("
        SELECT lst.name, lst.color, COUNT(*) as count
        FROM leads l
        JOIN lead_statuses lst ON l.lead_status_id = lst.id
        WHERE l.is_deleted = 0
        GROUP BY l.lead_status_id, lst.name, lst.color
    ");
    $stmt->execute();
    $status_distribution = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Lead source distribution for chart
    $stmt = $conn->prepare("
        SELECT ls.name, COUNT(*) as count
        FROM leads l
        JOIN lead_sources ls ON l.lead_source_id = ls.id
        WHERE l.is_deleted = 0
        GROUP BY l.lead_source_id, ls.name
        ORDER BY count DESC
        LIMIT 5
    ");
    $stmt->execute();
    $source_distribution = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Monthly leads trend (last 6 months)
    $stmt = $conn->prepare("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(*) as count
        FROM leads 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH) AND is_deleted = 0
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month
    ");
    $stmt->execute();
    $monthly_trend = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // User performance (if admin)
    $user_performance = [];
    if (hasPermission('admin')) {
        $stmt = $conn->prepare("
            SELECT 
                u.full_name,
                COUNT(l.id) as total_leads,
                SUM(CASE WHEN l.is_converted = 1 THEN 1 ELSE 0 END) as converted_leads,
                ROUND(SUM(CASE WHEN l.is_converted = 1 THEN 1 ELSE 0 END) * 100.0 / NULLIF(COUNT(l.id), 0), 2) as conversion_rate
            FROM users u
            LEFT JOIN leads l ON l.assigned_to = u.id AND l.is_deleted = 0
            WHERE u.is_active = 1 AND u.is_deleted = 0
            GROUP BY u.id, u.full_name
            HAVING total_leads > 0
            ORDER BY conversion_rate DESC
            LIMIT 5
        ");
        $stmt->execute();
        $user_performance = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
} catch (Exception $e) {
    error_log("Dashboard error: " . $e->getMessage());
    $error_message = "Unable to load dashboard data.";
}

// Calculate conversion rate
$conversion_rate = $total_leads > 0 ? round(($converted_leads / $total_leads) * 100, 2) : 0;
?>

<!-- Dashboard Stats Cards -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="stats-card">
            <div class="d-flex justify-content-between">
                <div>
                    <h3><?php echo number_format($total_leads); ?></h3>
                    <p>Total Leads</p>
                    <small class="text-white-50">
                        <i class="fas fa-plus"></i> <?php echo $today_leads; ?> today
                    </small>
                </div>
                <i class="fas fa-users"></i>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6">
        <div class="stats-card bg-success">
            <div class="d-flex justify-content-between">
                <div>
                    <h3><?php echo number_format($converted_leads); ?></h3>
                    <p>Converted Leads</p>
                    <small class="text-white-50">
                        <i class="fas fa-percentage"></i> <?php echo $conversion_rate; ?>% rate
                    </small>
                </div>
                <i class="fas fa-trophy"></i>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6">
        <div class="stats-card bg-warning">
            <div class="d-flex justify-content-between">
                <div>
                    <h3><?php echo number_format($followups_today); ?></h3>
                    <p>Follow-ups Today</p>
                    <small class="text-white-50">
                        <i class="fas fa-exclamation-triangle"></i> <?php echo $overdue_followups; ?> overdue
                    </small>
                </div>
                <i class="fas fa-calendar-check"></i>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6">
        <div class="stats-card bg-info">
            <div class="d-flex justify-content-between">
                <div>
                    <h3><?php echo formatCurrency($month_payments); ?></h3>
                    <p>This Month Revenue</p>
                    <small class="text-white-50">
                        <i class="fas fa-clock"></i> <?php echo formatCurrency($pending_payments); ?> pending
                    </small>
                </div>
                <i class="fas fa-rupee-sign"></i>
            </div>
        </div>
    </div>
</div>

<!-- Charts and Quick Actions Row -->
<div class="row mb-4">
    <!-- Lead Status Chart -->
    <div class="col-xl-4 col-lg-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-chart-pie"></i> Lead Status Distribution</h5>
            </div>
            <div class="card-body">
                <canvas id="statusChart" width="400" height="400"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Monthly Trend Chart -->
    <div class="col-xl-4 col-lg-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-chart-line"></i> Monthly Leads Trend</h5>
            </div>
            <div class="card-body">
                <canvas id="trendChart" width="400" height="400"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="col-xl-4 col-lg-12 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-bolt"></i> Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="<?php echo APP_URL; ?>/leads/add_lead.php" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i> Add New Lead
                    </a>
                    <a href="<?php echo APP_URL; ?>/leads/?filter=follow_up_today" class="btn btn-warning">
                        <i class="fas fa-calendar"></i> Today's Follow-ups
                    </a>
                    <a href="<?php echo APP_URL; ?>/modules/dsr/submit_dsr.php" class="btn btn-info">
                        <i class="fas fa-chart-line"></i> Submit Daily Report
                    </a>
                    <a href="<?php echo APP_URL; ?>/modules/payments/add_payment.php" class="btn btn-success">
                        <i class="fas fa-credit-card"></i> Add Payment
                    </a>
                    <?php if (hasPermission('admin')): ?>
                    <a href="<?php echo APP_URL; ?>/modules/analytics/" class="btn btn-secondary">
                        <i class="fas fa-chart-bar"></i> View Analytics
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activities and Performance -->
<div class="row">
    <!-- Recent Leads -->
    <div class="col-xl-8 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-clock"></i> Recent Leads</h5>
                <a href="<?php echo APP_URL; ?>/leads/" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Company</th>
                                <th>Status</th>
                                <th>Source</th>
                                <th>Assigned To</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recent_leads)): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        <i class="fas fa-inbox fa-2x mb-2"></i><br>
                                        No leads found. <a href="<?php echo APP_URL; ?>/leads/add_lead.php">Add your first lead</a>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recent_leads as $lead): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-sm bg-primary rounded-circle d-flex align-items-center justify-content-center me-2">
                                                    <span class="text-white fw-bold">
                                                        <?php echo strtoupper(substr($lead['first_name'], 0, 1)); ?>
                                                    </span>
                                                </div>
                                                <div>
                                                    <div class="fw-bold"><?php echo htmlspecialchars($lead['first_name'] . ' ' . $lead['last_name']); ?></div>
                                                    <small class="text-muted"><?php echo htmlspecialchars($lead['phone']); ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($lead['company'] ?: '-'); ?></td>
                                        <td>
                                            <span class="badge bg-primary"><?php echo htmlspecialchars($lead['status_name'] ?: 'New'); ?></span>
                                        </td>
                                        <td><?php echo htmlspecialchars($lead['source_name'] ?: '-'); ?></td>
                                        <td><?php echo htmlspecialchars($lead['assigned_name'] ?: 'Unassigned'); ?></td>
                                        <td><?php echo formatDate($lead['created_at']); ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="<?php echo APP_URL; ?>/leads/view_lead.php?id=<?php echo $lead['id']; ?>" 
                                                   class="btn btn-outline-primary" title="View">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="<?php echo APP_URL; ?>/leads/edit_lead.php?id=<?php echo $lead['id']; ?>" 
                                                   class="btn btn-outline-secondary" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- User Performance (Admin Only) -->
    <?php if (hasPermission('admin') && !empty($user_performance)): ?>
    <div class="col-xl-4 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-star"></i> Top Performers</h5>
            </div>
            <div class="card-body">
                <?php foreach ($user_performance as $user): ?>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <div class="fw-bold"><?php echo htmlspecialchars($user['full_name']); ?></div>
                            <small class="text-muted">
                                <?php echo $user['converted_leads']; ?>/<?php echo $user['total_leads']; ?> converted
                            </small>
                        </div>
                        <div class="text-end">
                            <div class="fw-bold text-success"><?php echo $user['conversion_rate']; ?>%</div>
                            <div class="progress" style="width: 80px; height: 6px;">
                                <div class="progress-bar bg-success" style="width: <?php echo min($user['conversion_rate'], 100); ?>%"></div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Lead Sources (if no user performance or not admin) -->
    <?php if (!hasPermission('admin') || empty($user_performance)): ?>
    <div class="col-xl-4 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-funnel-dollar"></i> Top Lead Sources</h5>
            </div>
            <div class="card-body">
                <?php if (empty($source_distribution)): ?>
                    <p class="text-muted text-center">No data available</p>
                <?php else: ?>
                    <?php 
                    $total_source_leads = array_sum(array_column($source_distribution, 'count'));
                    foreach ($source_distribution as $source): 
                        $percentage = $total_source_leads > 0 ? round(($source['count'] / $total_source_leads) * 100, 1) : 0;
                    ?>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <div class="fw-bold"><?php echo htmlspecialchars($source['name']); ?></div>
                                <small class="text-muted"><?php echo $source['count']; ?> leads</small>
                            </div>
                            <div class="text-end">
                                <div class="fw-bold"><?php echo $percentage; ?>%</div>
                                <div class="progress" style="width: 80px; height: 6px;">
                                    <div class="progress-bar" style="width: <?php echo $percentage; ?>%"></div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Chart.js Scripts -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Status Distribution Chart
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode(array_column($status_distribution, 'name')); ?>,
            datasets: [{
                data: <?php echo json_encode(array_column($status_distribution, 'count')); ?>,
                backgroundColor: <?php echo json_encode(array_column($status_distribution, 'color')); ?>,
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
    
    // Monthly Trend Chart
    const trendCtx = document.getElementById('trendChart').getContext('2d');
    new Chart(trendCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode(array_column($monthly_trend, 'month')); ?>,
            datasets: [{
                label: 'Leads',
                data: <?php echo json_encode(array_column($monthly_trend, 'count')); ?>,
                borderColor: '#007bff',
                backgroundColor: 'rgba(0, 123, 255, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
});
</script>

<?php include_once dirname(__DIR__) . '/includes/footer.php'; ?>