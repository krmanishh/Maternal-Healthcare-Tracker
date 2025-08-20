<?php
require_once 'backend/auth/auth.php';

$auth = new Auth();
$auth->requireRole('doctor_asha');

$db = getDBConnection();
$user = $auth->getCurrentUser();

// Get assigned patients
$stmt = $db->prepare("SELECT p.*, u.full_name, u.phone, u.email,
                      DATEDIFF(CURDATE(), p.lmp_date) as days_pregnant,
                      (SELECT COUNT(*) FROM visits WHERE pregnancy_id = p.id) as visit_count,
                      (SELECT MAX(visit_date) FROM visits WHERE pregnancy_id = p.id) as last_visit_date
                      FROM pregnancies p 
                      JOIN users u ON p.user_id = u.id 
                      WHERE p.assigned_doctor_id = ? AND p.is_active = 1
                      ORDER BY p.risk_level DESC, p.current_week DESC");
$stmt->execute([$user['id']]);
$patients = $stmt->fetchAll();

// Get high-risk alerts
$stmt = $db->prepare("SELECT ra.*, p.id as pregnancy_id, u.full_name as patient_name
                     FROM risk_alerts ra
                     JOIN pregnancies p ON ra.pregnancy_id = p.id
                     JOIN users u ON p.user_id = u.id
                     WHERE p.assigned_doctor_id = ? AND ra.is_resolved = 0
                     ORDER BY ra.severity DESC, ra.detected_at DESC
                     LIMIT 10");
$stmt->execute([$user['id']]);
$alerts = $stmt->fetchAll();

// Get recent visits
$stmt = $db->prepare("SELECT v.*, p.id as pregnancy_id, u.full_name as patient_name
                     FROM visits v
                     JOIN pregnancies p ON v.pregnancy_id = p.id
                     JOIN users u ON p.user_id = u.id
                     WHERE v.doctor_id = ?
                     ORDER BY v.visit_date DESC
                     LIMIT 10");
$stmt->execute([$user['id']]);
$recentVisits = $stmt->fetchAll();

// Statistics
$stats = [
    'total_patients' => count($patients),
    'high_risk' => count(array_filter($patients, function($p) { return $p['risk_level'] === 'high'; })),
    'pending_alerts' => count($alerts),
    'visits_today' => 0
];

// Count today's visits
$stmt = $db->prepare("SELECT COUNT(*) FROM visits v
                     JOIN pregnancies p ON v.pregnancy_id = p.id
                     WHERE p.assigned_doctor_id = ? AND DATE(v.visit_date) = CURDATE()");
$stmt->execute([$user['id']]);
$stats['visits_today'] = $stmt->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard - Maternal Healthcare Tracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="frontend/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand" href="doctor_dashboard.php">
                <i class="fas fa-user-md"></i> Maternal Healthcare - Doctor Portal
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="doctor_dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="doctor_patients.php"><i class="fas fa-users"></i> My Patients</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="doctor_visits.php"><i class="fas fa-stethoscope"></i> Visits</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user"></i> Dr. <?php echo htmlspecialchars($user['full_name']); ?>
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
                            <a class="nav-link active" href="doctor_dashboard.php">
                                <i class="fas fa-home"></i> Overview
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="doctor_patients.php">
                                <i class="fas fa-users"></i> All Patients
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="add_visit.php">
                                <i class="fas fa-plus"></i> Record Visit
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="doctor_alerts.php">
                                <i class="fas fa-exclamation-triangle"></i> Risk Alerts
                                <?php if ($stats['pending_alerts'] > 0): ?>
                                <span class="badge bg-danger ms-1"><?php echo $stats['pending_alerts']; ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="doctor_reports.php">
                                <i class="fas fa-chart-bar"></i> Reports
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
                        <h1><i class="fas fa-user-md"></i> Welcome, Dr. <?php echo htmlspecialchars($user['full_name']); ?></h1>
                        <p class="mb-0">Managing maternal healthcare for your patients</p>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="card stats-card bg-primary text-white">
                            <div class="card-body">
                                <i class="fas fa-users"></i>
                                <div class="stats-number"><?php echo $stats['total_patients']; ?></div>
                                <div>Total Patients</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="card stats-card bg-danger text-white">
                            <div class="card-body">
                                <i class="fas fa-exclamation-triangle"></i>
                                <div class="stats-number"><?php echo $stats['high_risk']; ?></div>
                                <div>High Risk</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="card stats-card bg-warning text-white">
                            <div class="card-body">
                                <i class="fas fa-bell"></i>
                                <div class="stats-number"><?php echo $stats['pending_alerts']; ?></div>
                                <div>Pending Alerts</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3">
                        <div class="card stats-card bg-success text-white">
                            <div class="card-body">
                                <i class="fas fa-calendar-day"></i>
                                <div class="stats-number"><?php echo $stats['visits_today']; ?></div>
                                <div>Visits Today</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- High-Risk Alerts -->
                    <div class="col-lg-6 mb-4">
                        <div class="card">
                            <div class="card-header bg-danger text-white">
                                <i class="fas fa-exclamation-triangle"></i> High-Risk Alerts
                                <a href="doctor_alerts.php" class="btn btn-sm btn-light float-end">View All</a>
                            </div>
                            <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                                <?php if (empty($alerts)): ?>
                                    <div class="text-center text-muted py-4">
                                        <i class="fas fa-check-circle fa-3x"></i>
                                        <p class="mt-2">No active alerts</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($alerts as $alert): ?>
                                    <div class="alert alert-<?php echo $alert['severity'] === 'critical' ? 'danger' : ($alert['severity'] === 'high' ? 'warning' : 'info'); ?> mb-2">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <strong><?php echo htmlspecialchars($alert['patient_name']); ?></strong>
                                                <p class="mb-1"><?php echo htmlspecialchars($alert['alert_message']); ?></p>
                                                <small>
                                                    <i class="fas fa-clock"></i> 
                                                    <?php echo date('M j, Y g:i A', strtotime($alert['detected_at'])); ?>
                                                </small>
                                            </div>
                                            <div>
                                                <span class="badge bg-<?php echo $alert['severity'] === 'critical' ? 'danger' : ($alert['severity'] === 'high' ? 'warning' : 'info'); ?>">
                                                    <?php echo ucfirst($alert['severity']); ?>
                                                </span>
                                                <button class="btn btn-sm btn-outline-success ms-1" 
                                                        onclick="resolveAlert(<?php echo $alert['id']; ?>)">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Patients -->
                    <div class="col-lg-6 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-users"></i> My Patients
                                <a href="doctor_patients.php" class="btn btn-sm btn-outline-primary float-end">View All</a>
                            </div>
                            <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                                <?php if (empty($patients)): ?>
                                    <div class="text-center text-muted py-4">
                                        <i class="fas fa-user-plus fa-3x"></i>
                                        <p class="mt-2">No patients assigned</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach (array_slice($patients, 0, 8) as $patient): ?>
                                    <div class="d-flex justify-content-between align-items-center mb-3 p-2 border rounded">
                                        <div>
                                            <strong><?php echo htmlspecialchars($patient['full_name']); ?></strong>
                                            <br>
                                            <small class="text-muted">
                                                Week <?php echo $patient['current_week']; ?> | 
                                                <?php echo $patient['visit_count']; ?> visits |
                                                Last: <?php echo $patient['last_visit_date'] ? date('M j', strtotime($patient['last_visit_date'])) : 'Never'; ?>
                                            </small>
                                        </div>
                                        <div class="text-end">
                                            <span class="badge risk-<?php echo $patient['risk_level']; ?> d-block mb-1">
                                                <?php echo ucfirst($patient['risk_level']); ?> Risk
                                            </span>
                                            <a href="add_visit.php?patient_id=<?php echo $patient['id']; ?>" class="btn btn-sm btn-primary">
                                                <i class="fas fa-plus"></i> Visit
                                            </a>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Visits -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-stethoscope"></i> Recent ANC Visits
                                <a href="doctor_visits.php" class="btn btn-sm btn-outline-primary float-end">View All</a>
                            </div>
                            <div class="card-body">
                                <?php if (empty($recentVisits)): ?>
                                    <div class="text-center text-muted py-4">
                                        <i class="fas fa-stethoscope fa-3x"></i>
                                        <p class="mt-2">No recent visits</p>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Patient</th>
                                                    <th>Week</th>
                                                    <th>Type</th>
                                                    <th>BP</th>
                                                    <th>Hb</th>
                                                    <th>Weight</th>
                                                    <th>Next Visit</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach (array_slice($recentVisits, 0, 5) as $visit): ?>
                                                <tr>
                                                    <td><?php echo date('M j, Y', strtotime($visit['visit_date'])); ?></td>
                                                    <td><?php echo htmlspecialchars($visit['patient_name']); ?></td>
                                                    <td><?php echo $visit['gestational_week']; ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php echo $visit['visit_type'] === 'emergency' ? 'danger' : 'info'; ?>">
                                                            <?php echo ucfirst($visit['visit_type']); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo $visit['blood_pressure_systolic'] . '/' . $visit['blood_pressure_diastolic']; ?></td>
                                                    <td><?php echo $visit['hemoglobin']; ?></td>
                                                    <td><?php echo $visit['weight'] . ' kg'; ?></td>
                                                    <td><?php echo $visit['next_visit_date'] ? date('M j, Y', strtotime($visit['next_visit_date'])) : 'TBD'; ?></td>
                                                    <td>
                                                        <a href="view_visit.php?id=<?php echo $visit['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function resolveAlert(alertId) {
            if (confirm('Mark this alert as resolved?')) {
                fetch('backend/api/resolve_alert.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        alert_id: alertId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while resolving the alert');
                });
            }
        }

        // Auto-refresh every 5 minutes
        setTimeout(function() {
            location.reload();
        }, 300000);
    </script>
</body>
</html>
