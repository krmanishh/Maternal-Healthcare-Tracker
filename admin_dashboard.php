<?php
require_once 'backend/auth/auth.php';

$auth = new Auth();
$auth->requireRole('admin');

$db = getDBConnection();
$user = $auth->getCurrentUser();

// Get analytics data
$analytics = [];

// Total pregnancies
$stmt = $db->prepare("SELECT COUNT(*) as total FROM pregnancies WHERE is_active = 1");
$stmt->execute();
$analytics['total_pregnancies'] = $stmt->fetchColumn();

// ANC compliance (patients with at least one visit in last 30 days)
$stmt = $db->prepare("SELECT COUNT(DISTINCT p.id) as compliant
                     FROM pregnancies p
                     JOIN visits v ON p.id = v.pregnancy_id
                     WHERE p.is_active = 1 AND v.visit_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
$stmt->execute();
$analytics['anc_compliant'] = $stmt->fetchColumn();
$analytics['anc_compliance_rate'] = $analytics['total_pregnancies'] > 0 ? 
    round(($analytics['anc_compliant'] / $analytics['total_pregnancies']) * 100, 1) : 0;

// High-risk cases
$stmt = $db->prepare("SELECT COUNT(*) as high_risk FROM pregnancies WHERE is_active = 1 AND risk_level = 'high'");
$stmt->execute();
$analytics['high_risk_cases'] = $stmt->fetchColumn();

// Moderate risk cases
$stmt = $db->prepare("SELECT COUNT(*) as moderate_risk FROM pregnancies WHERE is_active = 1 AND risk_level = 'moderate'");
$stmt->execute();
$analytics['moderate_risk_cases'] = $stmt->fetchColumn();

// Low risk cases
$analytics['low_risk_cases'] = $analytics['total_pregnancies'] - $analytics['high_risk_cases'] - $analytics['moderate_risk_cases'];

// Total visits this month
$stmt = $db->prepare("SELECT COUNT(*) as visits FROM visits WHERE MONTH(visit_date) = MONTH(CURDATE()) AND YEAR(visit_date) = YEAR(CURDATE())");
$stmt->execute();
$analytics['visits_this_month'] = $stmt->fetchColumn();

// Pending alerts
$stmt = $db->prepare("SELECT COUNT(*) as pending FROM risk_alerts WHERE is_resolved = 0");
$stmt->execute();
$analytics['pending_alerts'] = $stmt->fetchColumn();

// Doctors count
$stmt = $db->prepare("SELECT COUNT(*) as doctors FROM users WHERE user_role = 'doctor_asha' AND is_active = 1");
$stmt->execute();
$analytics['total_doctors'] = $stmt->fetchColumn();

// Trimester distribution
$stmt = $db->prepare("SELECT current_trimester, COUNT(*) as count 
                     FROM pregnancies 
                     WHERE is_active = 1 
                     GROUP BY current_trimester 
                     ORDER BY current_trimester");
$stmt->execute();
$trimester_data = $stmt->fetchAll();

// Risk level distribution for chart
$risk_distribution = [
    'low' => $analytics['low_risk_cases'],
    'moderate' => $analytics['moderate_risk_cases'],
    'high' => $analytics['high_risk_cases']
];

// Recent activities
$stmt = $db->prepare("SELECT 'visit' as type, v.visit_date as date, u.full_name as patient_name, d.full_name as doctor_name
                     FROM visits v
                     JOIN pregnancies p ON v.pregnancy_id = p.id
                     JOIN users u ON p.user_id = u.id
                     JOIN users d ON v.doctor_id = d.id
                     ORDER BY v.visit_date DESC LIMIT 10");
$stmt->execute();
$recent_activities = $stmt->fetchAll();

// Monthly stats for last 6 months
$monthly_stats = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $month_name = date('M Y', strtotime("-$i months"));
    
    $stmt = $db->prepare("SELECT COUNT(*) FROM visits WHERE DATE_FORMAT(visit_date, '%Y-%m') = ?");
    $stmt->execute([$month]);
    $visits = $stmt->fetchColumn();
    
    $stmt = $db->prepare("SELECT COUNT(*) FROM pregnancies WHERE DATE_FORMAT(created_at, '%Y-%m') = ? AND is_active = 1");
    $stmt->execute([$month]);
    $registrations = $stmt->fetchColumn();
    
    $monthly_stats[] = [
        'month' => $month_name,
        'visits' => $visits,
        'registrations' => $registrations
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Maternal Healthcare Tracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="frontend/css/style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand" href="admin_dashboard.php">
                <i class="fas fa-cog"></i> Maternal Healthcare - Admin Portal
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="admin_dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin_users.php"><i class="fas fa-users"></i> Users</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin_reports.php"><i class="fas fa-chart-bar"></i> Reports</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-shield"></i> <?php echo htmlspecialchars($user['full_name']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user-edit"></i> Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 p-0">
                <div class="sidebar">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="admin_dashboard.php">
                                <i class="fas fa-home"></i> Overview
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin_users.php">
                                <i class="fas fa-users"></i> User Management
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin_pregnancies.php">
                                <i class="fas fa-baby"></i> Pregnancies
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin_doctors.php">
                                <i class="fas fa-user-md"></i> Doctors/ASHA
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin_alerts.php">
                                <i class="fas fa-exclamation-triangle"></i> Risk Alerts
                                <?php if ($analytics['pending_alerts'] > 0): ?>
                                <span class="badge bg-danger ms-1"><?php echo $analytics['pending_alerts']; ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin_reports.php">
                                <i class="fas fa-chart-bar"></i> Reports & Export
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin_settings.php">
                                <i class="fas fa-cog"></i> Settings
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-10">
                <!-- Welcome Header -->
                <div class="dashboard-header mb-4">
                    <div class="container">
                        <h1><i class="fas fa-cog"></i> Admin Dashboard</h1>
                        <p class="mb-0">System overview and analytics for maternal healthcare management</p>
                    </div>
                </div>

                <!-- Statistics Cards Row 1 -->
                <div class="row mb-4">
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="card stats-card bg-primary text-white">
                            <div class="card-body">
                                <i class="fas fa-baby"></i>
                                <div class="stats-number"><?php echo $analytics['total_pregnancies']; ?></div>
                                <div>Total Pregnancies</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="card stats-card bg-success text-white">
                            <div class="card-body">
                                <i class="fas fa-check-circle"></i>
                                <div class="stats-number"><?php echo $analytics['anc_compliance_rate']; ?>%</div>
                                <div>ANC Compliance</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="card stats-card bg-danger text-white">
                            <div class="card-body">
                                <i class="fas fa-exclamation-triangle"></i>
                                <div class="stats-number"><?php echo $analytics['high_risk_cases']; ?></div>
                                <div>High Risk Cases</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="card stats-card bg-info text-white">
                            <div class="card-body">
                                <i class="fas fa-stethoscope"></i>
                                <div class="stats-number"><?php echo $analytics['visits_this_month']; ?></div>
                                <div>Visits This Month</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards Row 2 -->
                <div class="row mb-4">
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="card stats-card bg-warning text-white">
                            <div class="card-body">
                                <i class="fas fa-exclamation"></i>
                                <div class="stats-number"><?php echo $analytics['moderate_risk_cases']; ?></div>
                                <div>Moderate Risk</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="card stats-card bg-success text-white">
                            <div class="card-body">
                                <i class="fas fa-heart"></i>
                                <div class="stats-number"><?php echo $analytics['low_risk_cases']; ?></div>
                                <div>Low Risk</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="card stats-card bg-secondary text-white">
                            <div class="card-body">
                                <i class="fas fa-user-md"></i>
                                <div class="stats-number"><?php echo $analytics['total_doctors']; ?></div>
                                <div>Doctors/ASHA</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="card stats-card bg-danger text-white">
                            <div class="card-body">
                                <i class="fas fa-bell"></i>
                                <div class="stats-number"><?php echo $analytics['pending_alerts']; ?></div>
                                <div>Pending Alerts</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="row mb-4">
                    <!-- Risk Distribution Pie Chart -->
                    <div class="col-lg-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-chart-pie"></i> Risk Level Distribution
                            </div>
                            <div class="card-body">
                                <canvas id="riskChart" height="300"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Trimester Distribution Bar Chart -->
                    <div class="col-lg-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-chart-bar"></i> Trimester Distribution
                            </div>
                            <div class="card-body">
                                <canvas id="trimesterChart" height="300"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Monthly Trends -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-chart-line"></i> Monthly Trends (Last 6 Months)
                                <div class="float-end">
                                    <button class="btn btn-sm btn-outline-primary" onclick="exportReport('monthly')">
                                        <i class="fas fa-download"></i> Export
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <canvas id="monthlyChart" height="100"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activities and Quick Actions -->
                <div class="row">
                    <!-- Recent Activities -->
                    <div class="col-lg-8 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-history"></i> Recent Activities
                            </div>
                            <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                                <?php if (empty($recent_activities)): ?>
                                    <div class="text-center text-muted py-4">
                                        <i class="fas fa-history fa-3x"></i>
                                        <p class="mt-2">No recent activities</p>
                                    </div>
                                <?php else: ?>
                                    <div class="timeline">
                                        <?php foreach ($recent_activities as $activity): ?>
                                        <div class="timeline-item">
                                            <h6><i class="fas fa-stethoscope text-primary"></i> ANC Visit</h6>
                                            <p><strong><?php echo htmlspecialchars($activity['patient_name']); ?></strong> visited <strong><?php echo htmlspecialchars($activity['doctor_name']); ?></strong></p>
                                            <small class="text-muted">
                                                <i class="fas fa-clock"></i> <?php echo date('M j, Y g:i A', strtotime($activity['date'])); ?>
                                            </small>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="col-lg-4 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-bolt"></i> Quick Actions
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <a href="admin_reports.php" class="btn btn-outline-primary">
                                        <i class="fas fa-download"></i> Generate Reports
                                    </a>
                                    <a href="admin_users.php" class="btn btn-outline-success">
                                        <i class="fas fa-user-plus"></i> Manage Users
                                    </a>
                                    <a href="admin_doctors.php" class="btn btn-outline-info">
                                        <i class="fas fa-user-md"></i> Assign Doctors
                                    </a>
                                    <a href="admin_alerts.php" class="btn btn-outline-warning">
                                        <i class="fas fa-exclamation-triangle"></i> View Alerts
                                    </a>
                                    <a href="admin_settings.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-cog"></i> System Settings
                                    </a>
                                </div>

                                <!-- System Status -->
                                <div class="mt-4">
                                    <h6>System Status</h6>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Database</span>
                                        <span class="badge bg-success">Online</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Email Service</span>
                                        <span class="badge bg-success">Active</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>SMS Service</span>
                                        <span class="badge bg-warning">Limited</span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span>Backup Status</span>
                                        <span class="badge bg-success">Updated</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Risk Distribution Pie Chart
        const riskCtx = document.getElementById('riskChart').getContext('2d');
        new Chart(riskCtx, {
            type: 'pie',
            data: {
                labels: ['Low Risk', 'Moderate Risk', 'High Risk'],
                datasets: [{
                    data: [<?php echo $risk_distribution['low']; ?>, <?php echo $risk_distribution['moderate']; ?>, <?php echo $risk_distribution['high']; ?>],
                    backgroundColor: ['#4caf50', '#ff9800', '#f44336'],
                    borderWidth: 2
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

        // Trimester Distribution Bar Chart
        const trimesterCtx = document.getElementById('trimesterChart').getContext('2d');
        new Chart(trimesterCtx, {
            type: 'bar',
            data: {
                labels: [<?php foreach ($trimester_data as $t) echo "'Trimester " . $t['current_trimester'] . "',"; ?>],
                datasets: [{
                    label: 'Number of Patients',
                    data: [<?php foreach ($trimester_data as $t) echo $t['count'] . ','; ?>],
                    backgroundColor: '#e91e63',
                    borderColor: '#c2185b',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });

        // Monthly Trends Line Chart
        const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
        new Chart(monthlyCtx, {
            type: 'line',
            data: {
                labels: [<?php foreach ($monthly_stats as $stat) echo "'" . $stat['month'] . "',"; ?>],
                datasets: [{
                    label: 'Visits',
                    data: [<?php foreach ($monthly_stats as $stat) echo $stat['visits'] . ','; ?>],
                    borderColor: '#2196f3',
                    backgroundColor: 'rgba(33, 150, 243, 0.1)',
                    tension: 0.4
                }, {
                    label: 'New Registrations',
                    data: [<?php foreach ($monthly_stats as $stat) echo $stat['registrations'] . ','; ?>],
                    borderColor: '#4caf50',
                    backgroundColor: 'rgba(76, 175, 80, 0.1)',
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
                }
            }
        });

        function exportReport(type) {
            window.open('admin_reports.php?export=' + type, '_blank');
        }

        // Auto-refresh every 5 minutes
        setTimeout(function() {
            location.reload();
        }, 300000);
    </script>
</body>
</html>
