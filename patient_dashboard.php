<?php
require_once 'backend/auth/auth.php';

$auth = new Auth();
$auth->requireRole('pregnant_woman');

$db = getDBConnection();
$user = $auth->getCurrentUser();

// Get pregnancy details
$stmt = $db->prepare("SELECT p.*, u.full_name as doctor_name FROM pregnancies p 
                     LEFT JOIN users u ON p.assigned_doctor_id = u.id 
                     WHERE p.user_id = ? AND p.is_active = 1");
$stmt->execute([$user['id']]);
$pregnancy = $stmt->fetch();

// Get nutrition tips for current trimester
$nutritionTips = [];
if ($pregnancy) {
    $stmt = $db->prepare("SELECT * FROM nutrition_tips WHERE trimester = ? AND is_active = 1 ORDER BY category, title");
    $stmt->execute([$pregnancy['current_trimester']]);
    $nutritionTips = $stmt->fetchAll();
}

// Get emergency contacts
$stmt = $db->prepare("SELECT * FROM emergency_contacts WHERE is_active = 1 ORDER BY service_type");
$emergencyContacts = $stmt->fetchAll();

// Get recent visits
$recentVisits = [];
if ($pregnancy) {
    $stmt = $db->prepare("SELECT v.*, u.full_name as doctor_name FROM visits v
                         JOIN users u ON v.doctor_id = u.id
                         WHERE v.pregnancy_id = ? ORDER BY v.visit_date DESC LIMIT 5");
    $stmt->execute([$pregnancy['id']]);
    $recentVisits = $stmt->fetchAll();
}

// Get risk alerts
$riskAlerts = [];
if ($pregnancy) {
    $stmt = $db->prepare("SELECT * FROM risk_alerts WHERE pregnancy_id = ? AND is_resolved = 0 ORDER BY detected_at DESC");
    $stmt->execute([$pregnancy['id']]);
    $riskAlerts = $stmt->fetchAll();
}

// Calculate days until EDD
$daysUntilEDD = 0;
$progressPercent = 0;
if ($pregnancy) {
    $currentDate = new DateTime();
    $eddDate = new DateTime($pregnancy['edd_date']);
    $lmpDate = new DateTime($pregnancy['lmp_date']);
    
    $daysUntilEDD = max(0, $currentDate->diff($eddDate)->days);
    if ($eddDate < $currentDate) $daysUntilEDD = 0;
    
    $totalDays = 280; // 40 weeks
    $daysPassed = $currentDate->diff($lmpDate)->days;
    $progressPercent = min(100, ($daysPassed / $totalDays) * 100);
}

// Function to get trimester milestones
function getTrimesterMilestones($currentWeek) {
    $milestones = [
        ['week' => 4, 'title' => 'Pregnancy confirmed', 'completed' => $currentWeek >= 4],
        ['week' => 8, 'title' => 'First prenatal visit', 'completed' => $currentWeek >= 8],
        ['week' => 12, 'title' => 'End of first trimester', 'completed' => $currentWeek >= 12],
        ['week' => 16, 'title' => 'Gender determination possible', 'completed' => $currentWeek >= 16],
        ['week' => 20, 'title' => 'Anatomy scan', 'completed' => $currentWeek >= 20],
        ['week' => 24, 'title' => 'Viability milestone', 'completed' => $currentWeek >= 24],
        ['week' => 28, 'title' => 'Start of third trimester', 'completed' => $currentWeek >= 28],
        ['week' => 32, 'title' => 'Frequent monitoring begins', 'completed' => $currentWeek >= 32],
        ['week' => 36, 'title' => 'Full term approaches', 'completed' => $currentWeek >= 36],
        ['week' => 40, 'title' => 'Due date', 'completed' => $currentWeek >= 40]
    ];
    return $milestones;
}

$milestones = [];
if ($pregnancy) {
    $milestones = getTrimesterMilestones($pregnancy['current_week']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Pregnancy Dashboard - Maternal Healthcare Tracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="frontend/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="patient_dashboard.php">
                <i class="fas fa-baby"></i> Maternal Healthcare
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="patient_dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="patient_visits.php"><i class="fas fa-stethoscope"></i> My Visits</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($user['full_name']); ?>
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

    <?php if ($pregnancy): ?>
    <!-- Dashboard Header -->
    <div class="dashboard-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1><i class="fas fa-baby"></i> Welcome back, <?php echo htmlspecialchars($user['full_name']); ?>!</h1>
                    <p class="mb-0">You are currently in week <strong><?php echo $pregnancy['current_week']; ?></strong> of your pregnancy</p>
                </div>
                <div class="col-md-4 text-center">
                    <div class="pregnancy-week"><?php echo $pregnancy['current_week']; ?></div>
                    <div>weeks pregnant</div>
                </div>
            </div>
        </div>
    </div>

    <div class="container my-4">
        <!-- Risk Alerts -->
        <?php if (!empty($riskAlerts)): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-danger">
                    <div class="card-header bg-danger text-white">
                        <i class="fas fa-exclamation-triangle"></i> Important Alerts
                    </div>
                    <div class="card-body">
                        <?php foreach ($riskAlerts as $alert): ?>
                        <div class="alert alert-<?php echo $alert['severity'] === 'critical' ? 'danger' : ($alert['severity'] === 'high' ? 'warning' : 'info'); ?>">
                            <strong><?php echo ucfirst($alert['severity']); ?> Alert:</strong>
                            <?php echo htmlspecialchars($alert['alert_message']); ?>
                            <small class="d-block mt-1">Detected: <?php echo date('M j, Y g:i A', strtotime($alert['detected_at'])); ?></small>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="row">
            <!-- Pregnancy Progress -->
            <div class="col-lg-8 mb-4">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-chart-line"></i> Pregnancy Progress
                    </div>
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h6>Current Status</h6>
                                <p>Trimester: <span class="badge bg-primary">Trimester <?php echo $pregnancy['current_trimester']; ?></span></p>
                                <p>Risk Level: <span class="badge risk-<?php echo $pregnancy['risk_level']; ?>"><?php echo ucfirst($pregnancy['risk_level']); ?> Risk</span></p>
                                <p>Expected Due Date: <strong><?php echo date('M j, Y', strtotime($pregnancy['edd_date'])); ?></strong></p>
                            </div>
                            <div class="col-md-6">
                                <h6>Time Remaining</h6>
                                <div class="edd-countdown"><?php echo $daysUntilEDD; ?> days</div>
                                <div class="trimester-progress mt-2">
                                    <div class="trimester-progress-bar" style="width: <?php echo $progressPercent; ?>%"></div>
                                </div>
                                <small class="text-muted"><?php echo round($progressPercent); ?>% complete</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Emergency Contacts -->
            <div class="col-lg-4 mb-4">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-phone-alt"></i> Emergency Contacts
                    </div>
                    <div class="card-body">
                        <button class="btn btn-danger emergency-button w-100 mb-3" onclick="callAmbulance()">
                            <i class="fas fa-ambulance"></i> Call Ambulance
                        </button>
                        
                        <?php foreach ($emergencyContacts as $contact): ?>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <strong><?php echo htmlspecialchars($contact['service_name']); ?></strong><br>
                                <small class="text-muted"><?php echo htmlspecialchars($contact['location']); ?></small>
                            </div>
                            <a href="tel:<?php echo $contact['contact_number']; ?>" class="btn btn-outline-primary btn-sm">
                                <?php echo $contact['contact_number']; ?>
                            </a>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Timeline -->
            <div class="col-lg-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-timeline"></i> Pregnancy Timeline
                    </div>
                    <div class="card-body">
                        <div class="timeline">
                            <?php foreach ($milestones as $milestone): ?>
                            <div class="timeline-item <?php echo $milestone['completed'] ? 'completed' : ($milestone['week'] == $pregnancy['current_week'] ? 'active' : ''); ?>">
                                <h6>Week <?php echo $milestone['week']; ?></h6>
                                <p><?php echo $milestone['title']; ?></p>
                                <?php if ($milestone['completed']): ?>
                                    <small class="text-success"><i class="fas fa-check"></i> Completed</small>
                                <?php elseif ($milestone['week'] == $pregnancy['current_week']): ?>
                                    <small class="text-primary"><i class="fas fa-clock"></i> Current</small>
                                <?php else: ?>
                                    <small class="text-muted">Upcoming</small>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Nutrition Tips -->
            <div class="col-lg-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-apple-alt"></i> Nutrition Tips for Trimester <?php echo $pregnancy['current_trimester']; ?>
                    </div>
                    <div class="card-body">
                        <div style="max-height: 400px; overflow-y: auto;">
                            <?php foreach ($nutritionTips as $tip): ?>
                            <div class="nutrition-tip">
                                <h6><?php echo htmlspecialchars($tip['title']); ?></h6>
                                <p><?php echo htmlspecialchars($tip['description']); ?></p>
                                <small class="badge bg-secondary"><?php echo ucfirst($tip['category']); ?></small>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Visits -->
        <?php if (!empty($recentVisits)): ?>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-stethoscope"></i> Recent ANC Visits
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Doctor</th>
                                        <th>Type</th>
                                        <th>Week</th>
                                        <th>BP</th>
                                        <th>Hb</th>
                                        <th>Next Visit</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentVisits as $visit): ?>
                                    <tr>
                                        <td><?php echo date('M j, Y', strtotime($visit['visit_date'])); ?></td>
                                        <td><?php echo htmlspecialchars($visit['doctor_name']); ?></td>
                                        <td><span class="badge bg-info"><?php echo ucfirst($visit['visit_type']); ?></span></td>
                                        <td><?php echo $visit['gestational_week']; ?></td>
                                        <td><?php echo $visit['blood_pressure_systolic'] . '/' . $visit['blood_pressure_diastolic']; ?></td>
                                        <td><?php echo $visit['hemoglobin']; ?></td>
                                        <td><?php echo $visit['next_visit_date'] ? date('M j, Y', strtotime($visit['next_visit_date'])) : 'TBD'; ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="text-center">
                            <a href="patient_visits.php" class="btn btn-outline-primary">View All Visits</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <?php else: ?>
    <!-- No Pregnancy Record -->
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body text-center">
                        <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                        <h4>No Active Pregnancy Record</h4>
                        <p>We couldn't find an active pregnancy record for your account. This might be because:</p>
                        <ul class="list-unstyled">
                            <li>• Your registration is still being processed</li>
                            <li>• Your pregnancy record needs to be activated</li>
                            <li>• There was an issue during registration</li>
                        </ul>
                        <p>Please contact your healthcare provider or administrator for assistance.</p>
                        <a href="logout.php" class="btn btn-outline-primary">Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function callAmbulance() {
            if (confirm('Do you want to call emergency ambulance service (102)?')) {
                window.location.href = 'tel:102';
            }
        }
        
        // Auto-refresh alerts every 5 minutes
        setTimeout(function() {
            location.reload();
        }, 300000);
    </script>
</body>
</html>
